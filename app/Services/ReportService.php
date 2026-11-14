<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\JobProduct;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Support\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cifras de gestión de una sucursal en un periodo.
 *
 * Mismo criterio que el dashboard: ingreso = ventas de mostrador no anuladas (por fecha de venta)
 * + órdenes entregadas (por fecha de completado: mano de obra + repuestos).
 * La ganancia usa el costo real del lote FIFO del que salió cada repuesto.
 */
class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?int $branchId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();

        $sales = $this->salesQuery($branchId, $from, $to);
        $salesRevenue = (float) (clone $sales)->sum('total_amount');
        $saleIds = (clone $sales)->pluck('id');
        $salesCost = $this->costOfMovements('sale', $saleIds->map(fn ($id) => "Venta #{$id}"));

        $orders = ServiceOrder::forBranch($branchId)
            ->where('status', ServiceOrder::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$from, $to])
            ->with(['workshopJobs' => fn ($query) => $query
                ->where('status', '!=', WorkshopJob::STATUS_CANCELLED)
                ->with('jobProducts')])
            ->get();

        $jobs = $orders->flatMap->workshopJobs;
        $laborRevenue = (float) $jobs->sum('total_amount');
        $workshopLabor = (float) $jobs->sum('workshop_cost');
        $partsRevenue = (float) $jobs->sum(fn (WorkshopJob $job) => $job->jobProducts->sum('total_price'));
        $partsCost = $this->costOfMovements('job_usage', $jobs->flatMap(fn (WorkshopJob $job) => [
            "Trabajo #{$job->id}",
            "Trabajo Individual #{$job->id}",
            "Trabajo #{$job->id} - Orden #{$job->service_order_id}",
        ]));

        $mechanics = $this->mechanics($branchId, $from, $to);

        return [
            'income' => [
                'total' => $salesRevenue + $laborRevenue + $partsRevenue,
                'sales' => $salesRevenue,
                'orders' => $laborRevenue + $partsRevenue,
            ],
            'profit' => [
                'total' => $workshopLabor + ($salesRevenue - $salesCost) + ($partsRevenue - $partsCost),
                'labor' => $workshopLabor,
                'parts_margin' => ($salesRevenue - $salesCost) + ($partsRevenue - $partsCost),
            ],
            'sales_count' => $saleIds->count(),
            'orders' => [
                'received' => ServiceOrder::forBranch($branchId)->whereBetween('created_at', [$from, $to])->count(),
                'delivered' => $orders->count(),
            ],
            'commissions' => (float) $mechanics->sum('earnings'),
            'mechanics' => $mechanics,
            'daily' => $this->dailyIncome($branchId, $from, $to, $orders),
            'top_products' => $this->topProducts($saleIds, $jobs->pluck('id')),
            'payment_methods' => $this->paymentMethods($branchId, $from, $to),
            'inventory' => $this->inventory($branchId),
        ];
    }

    /**
     * Lo cobrado por método de pago: ventas de mostrador no anuladas (fecha de venta)
     * + comprobantes de servicio vigentes (fecha del comprobante).
     *
     * @return Collection<int, object{payment_method: string, total: float, sales: int, invoices: int}>
     */
    public function paymentMethods(?int $branchId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $sales = $this->salesQuery($branchId, $from->startOfDay(), $to->endOfDay())
            ->select('payment_method', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as items'))
            ->groupBy('payment_method')
            ->get();

        $invoices = Invoice::active()
            ->whereHas('serviceOrder', fn ($query) => $query->forBranch($branchId))
            ->whereDate('invoice_date', '>=', $from->toDateString())
            ->whereDate('invoice_date', '<=', $to->toDateString())
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as items'))
            ->groupBy('payment_method')
            ->get();

        $methods = [];
        foreach (['sales' => $sales, 'invoices' => $invoices] as $kind => $rows) {
            foreach ($rows as $row) {
                $label = PaymentMethod::label($row->payment_method);
                $methods[$label] ??= (object) ['payment_method' => $label, 'total' => 0.0, 'sales' => 0, 'invoices' => 0];
                $methods[$label]->total += (float) $row->total;
                $methods[$label]->{$kind} += (int) $row->items;
            }
        }

        return collect(array_values($methods))->sortByDesc('total')->values();
    }

    private function salesQuery(?int $branchId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return Sale::forBranch($branchId)
            ->where('status', '!=', 'anulada')
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString());
    }

    /**
     * Costo de las unidades que salieron con esas referencias, al costo del lote consumido.
     *
     * @param  Collection<int, string>  $references
     */
    private function costOfMovements(string $movementType, Collection $references): float
    {
        return (float) $references->unique()->chunk(500)->sum(fn (Collection $chunk) => InventoryMovement::query()
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->leftJoin('batches', 'batches.id', '=', 'inventory_movements.batch_id')
            ->where('inventory_movements.movement_type', $movementType)
            ->where('inventory_movements.quantity', '<', 0)
            ->whereIn('inventory_movements.reference', $chunk->values())
            ->sum(DB::raw('-inventory_movements.quantity * COALESCE(batches.cost_price, products.purchase_price)')));
    }

    /**
     * Comisiones por trabajos completados en el periodo (mismo criterio que el dashboard).
     *
     * @return Collection<int, array{name: string, is_active: bool, jobs: int, earnings: float}>
     */
    private function mechanics(?int $branchId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $completedInPeriod = fn ($query) => $query
            ->where('status', WorkshopJob::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$from, $to]);

        return Mechanic::forBranch($branchId)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereHas('workshopJobs', $completedInPeriod))
            ->withCount(['workshopJobs as jobs' => $completedInPeriod])
            ->withSum(['workshopJobs as earnings' => $completedInPeriod], 'mechanic_cost')
            ->get()
            ->map(fn (Mechanic $mechanic) => [
                'name' => $mechanic->name,
                'is_active' => (bool) $mechanic->is_active,
                'jobs' => (int) $mechanic->jobs,
                'earnings' => (float) $mechanic->earnings,
            ])
            ->sortByDesc('earnings')
            ->values();
    }

    /**
     * Ingreso por día (ventas + órdenes entregadas) hasta hoy como máximo.
     *
     * @param  Collection<int, ServiceOrder>  $orders
     * @return Collection<int, array{date: CarbonImmutable, sales: float, orders: float, total: float}>
     */
    private function dailyIncome(?int $branchId, CarbonImmutable $from, CarbonImmutable $to, Collection $orders): Collection
    {
        $salesByDay = $this->salesQuery($branchId, $from, $to)
            ->selectRaw('DATE(sale_date) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $ordersByDay = $orders
            ->groupBy(fn (ServiceOrder $order) => $order->completed_at->toDateString())
            ->map(fn (Collection $dayOrders) => $dayOrders->flatMap->workshopJobs->sum(
                fn (WorkshopJob $job) => $job->total_amount + $job->jobProducts->sum('total_price')
            ));

        $lastDay = $to->min(CarbonImmutable::now()->endOfDay());
        $days = collect();

        for ($day = $from; $day->lte($lastDay); $day = $day->addDay()) {
            $key = $day->toDateString();
            $sales = (float) ($salesByDay[$key] ?? 0);
            $ordersIncome = (float) ($ordersByDay[$key] ?? 0);

            $days->push(['date' => $day, 'sales' => $sales, 'orders' => $ordersIncome, 'total' => $sales + $ordersIncome]);
        }

        return $days;
    }

    /**
     * Repuestos con más unidades vendidas (mostrador + usados en órdenes entregadas).
     *
     * @param  Collection<int, int>  $saleIds
     * @param  Collection<int, int>  $jobIds
     * @return Collection<int, array{name: string, quantity: int, revenue: float}>
     */
    private function topProducts(Collection $saleIds, Collection $jobIds, int $limit = 5): Collection
    {
        $lines = collect();

        foreach ($saleIds->chunk(500) as $chunk) {
            $lines = $lines->concat(SaleProduct::whereIn('sale_id', $chunk->values())
                ->select('product_id', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(total_price) as revenue'))
                ->groupBy('product_id')
                ->get());
        }

        foreach ($jobIds->chunk(500) as $chunk) {
            $lines = $lines->concat(JobProduct::whereIn('job_id', $chunk->values())
                ->select('product_id', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(total_price) as revenue'))
                ->groupBy('product_id')
                ->get());
        }

        $totals = $lines->groupBy('product_id')
            ->map(fn (Collection $rows) => [
                'quantity' => (int) $rows->sum('quantity'),
                'revenue' => (float) $rows->sum('revenue'),
            ])
            ->sortByDesc('quantity')
            ->take($limit);

        $names = Product::withTrashed()->whereKey($totals->keys())->pluck('name', 'id');

        return $totals->map(fn (array $row, int $productId) => ['name' => $names[$productId] ?? 'Producto eliminado'] + $row)->values();
    }

    /**
     * Valor del inventario actual con el costo y precio de cada lote.
     *
     * @return array{products: int, low_stock: int, cost_value: float, sale_value: float}
     */
    private function inventory(?int $branchId): array
    {
        $products = Product::forBranch($branchId);

        $batches = Batch::query()
            ->join('products', 'products.id', '=', 'batches.product_id')
            ->whereIn('batches.product_id', (clone $products)->select('id'))
            ->where('batches.remaining_stock', '>', 0);

        return [
            'products' => (clone $products)->count(),
            'low_stock' => (clone $products)->whereColumn('stock', '<=', 'min_stock')->count(),
            'cost_value' => (float) (clone $batches)->sum(DB::raw('batches.remaining_stock * batches.cost_price')),
            'sale_value' => (float) (clone $batches)->sum(DB::raw('batches.remaining_stock * COALESCE(batches.sale_price, products.sale_price)')),
        ];
    }
}
