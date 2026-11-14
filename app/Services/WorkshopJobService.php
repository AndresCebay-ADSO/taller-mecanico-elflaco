<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\JobType;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use Illuminate\Support\Facades\DB;

class WorkshopJobService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * Agrega un trabajo a una orden y descuenta los repuestos usados.
     *
     * @param  array{job_type_id: int|string, mechanic_id: int|string, labor_cost: float|int|string, description: string}  $data
     * @param  array<int, array{id: int|string, quantity: int|string}>  $products
     *
     * @throws InsufficientStockException|BusinessRuleException
     */
    public function addToOrder(ServiceOrder $serviceOrder, array $data, array $products = []): WorkshopJob
    {
        return DB::transaction(function () use ($serviceOrder, $data, $products) {
            $order = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->firstOrFail();

            if (!$order->canReceiveJobs()) {
                throw new BusinessRuleException('No se pueden agregar trabajos a una orden completada o cancelada.', 'service_order');
            }

            $jobType = JobType::findOrFail($data['job_type_id']);

            if (!empty($products) && !$jobType->allow_products) {
                throw new BusinessRuleException("El tipo de trabajo \"{$jobType->name}\" no permite agregar productos.", 'products');
            }

            $this->ensureProductsBelongToBranch($products, $order->branch_id);

            $job = new WorkshopJob([
                'job_type_id' => $data['job_type_id'],
                'mechanic_id' => $data['mechanic_id'],
                'labor_cost' => $data['labor_cost'],
                'description' => $data['description'],
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone ?: 'N/A',
                'vehicle_info' => $order->vehicle_info,
                'status' => WorkshopJob::STATUS_PENDING,
                'started_at' => now(),
            ]);
            $job->service_order_id = $order->id;
            $job->save();

            foreach ($products as $item) {
                $productId = (int) $item['id'];
                $tramos = $this->inventory->deductStock($productId, (int) $item['quantity'], $this->reference($job), 'job_usage');

                foreach ($tramos as $tramo) {
                    $job->jobProducts()->create([
                        'product_id' => $productId,
                        'quantity' => $tramo['quantity'],
                        'unit_price' => $tramo['unit_price'],
                        'total_price' => $tramo['unit_price'] * $tramo['quantity'],
                    ]);
                }
            }

            if ($order->status === ServiceOrder::STATUS_PENDING) {
                $order->update([
                    'status' => ServiceOrder::STATUS_IN_PROGRESS,
                    'started_at' => $order->started_at ?? now(),
                ]);
            }

            return $job;
        }, 3);
    }

    /**
     * Crea una orden de servicio en la sucursal indicada con un unico trabajo.
     *
     * @param  array{customer_name: string, customer_phone?: string|null, vehicle_info: string, description: string}  $data
     * @param  array<int, array{id: int|string, quantity: int|string}>  $products
     */
    public function createStandalone(int $branchId, array $data, array $products = []): WorkshopJob
    {
        return DB::transaction(function () use ($branchId, $data, $products) {
            $order = ServiceOrder::create([
                'branch_id' => $branchId,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'vehicle_info' => $data['vehicle_info'],
                'service_description' => $data['description'],
                'status' => ServiceOrder::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            return $this->addToOrder($order, $data, $products);
        }, 3);
    }

    /**
     * Elimina un trabajo devolviendo sus repuestos a los lotes de origen.
     *
     * @throws BusinessRuleException
     */
    public function delete(WorkshopJob $job): void
    {
        DB::transaction(function () use ($job) {
            $order = $job->service_order_id
                ? ServiceOrder::whereKey($job->service_order_id)->lockForUpdate()->first()
                : null;

            if ($order && $order->activeInvoice()->exists()) {
                throw new BusinessRuleException('No se puede eliminar un trabajo de una orden ya facturada.', 'job');
            }

            $lockedJob = WorkshopJob::whereKey($job->id)->lockForUpdate()->firstOrFail();

            // Un trabajo cancelado ya devolvió sus repuestos: eliminarlo los devolvería dos veces.
            if ($lockedJob->status === WorkshopJob::STATUS_CANCELLED) {
                throw new BusinessRuleException('Este trabajo fue cancelado y sus repuestos ya volvieron al inventario.', 'job');
            }

            $this->returnParts($lockedJob, "Eliminacion Trabajo #{$lockedJob->id}");

            $lockedJob->delete();
        }, 3);
    }

    /**
     * Cancela una orden abierta: cancela sus trabajos y devuelve los repuestos a sus lotes de origen.
     * Si ya hay trabajos completados, la orden debe facturarse (o eliminarse esos trabajos) en lugar de cancelarse.
     *
     * @throws BusinessRuleException
     */
    public function cancelOrder(ServiceOrder $serviceOrder): void
    {
        DB::transaction(function () use ($serviceOrder) {
            $order = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->firstOrFail();

            if (!$order->canTransitionTo(ServiceOrder::STATUS_CANCELLED)) {
                throw new BusinessRuleException('Solo se pueden cancelar órdenes pendientes o en progreso.', 'status');
            }

            $jobs = WorkshopJob::where('service_order_id', $order->id)
                ->where('status', '!=', WorkshopJob::STATUS_CANCELLED)
                ->lockForUpdate()
                ->get();

            if ($jobs->contains(fn (WorkshopJob $job) => $job->status === WorkshopJob::STATUS_COMPLETED)) {
                throw new BusinessRuleException(
                    'La orden tiene trabajos completados: genera la factura o elimina esos trabajos antes de cancelarla.',
                    'status'
                );
            }

            foreach ($jobs as $job) {
                $this->returnParts($job, "Cancelacion Orden #{$order->id}");
                $job->update(['status' => WorkshopJob::STATUS_CANCELLED]);
            }

            $order->update([
                'status' => ServiceOrder::STATUS_CANCELLED,
                'completed_at' => null,
            ]);
        }, 3);
    }

    /**
     * Devuelve al inventario los repuestos consumidos por un trabajo, al mismo lote del que salieron.
     */
    private function returnParts(WorkshopJob $job, string $reference): void
    {
        $reversed = $this->inventory->reverseMovements($this->legacyReferences($job), ['job_usage'], $reference);

        // Trabajos anteriores al inventario por lotes no tienen movimientos: se devuelve lo registrado en el trabajo.
        if ($reversed === 0) {
            foreach ($job->jobProducts as $jobProduct) {
                $this->inventory->returnToStock(
                    $jobProduct->product_id,
                    $jobProduct->quantity,
                    $reference,
                    null,
                    $jobProduct->unit_price
                );
            }
        }
    }

    private function reference(WorkshopJob $job): string
    {
        return "Trabajo #{$job->id}";
    }

    /**
     * Formatos de referencia usados historicamente para los consumos de un trabajo.
     *
     * @return array<int, string>
     */
    private function legacyReferences(WorkshopJob $job): array
    {
        return [
            $this->reference($job),
            "Trabajo Individual #{$job->id}",
            "Trabajo #{$job->id} - Orden #{$job->service_order_id}",
        ];
    }

    /**
     * @param  array<int, array{id: int|string}>  $products
     */
    private function ensureProductsBelongToBranch(array $products, ?int $branchId): void
    {
        if (empty($products) || $branchId === null) {
            return;
        }

        $productIds = collect($products)->pluck('id')->map(fn ($id) => (int) $id)->unique();
        $matching = Product::whereKey($productIds)->where('branch_id', $branchId)->count();

        if ($matching !== $productIds->count()) {
            throw new BusinessRuleException('Uno de los productos no pertenece a la sucursal de la orden.', 'products');
        }
    }
}
