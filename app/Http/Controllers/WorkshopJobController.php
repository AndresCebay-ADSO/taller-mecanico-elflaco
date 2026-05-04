<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkshopJobController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkshopJob::with(['jobType', 'mechanic', 'serviceOrder']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->latest()->paginate(10);

        $jobTypes = JobType::where('is_active', true)->get();
        $mechanics = Mechanic::where('is_active', true)->get();
        $products = \App\Models\Product::where('stock', '>', 0)->get();
            
        return view('jobs.index', compact('jobs', 'jobTypes', 'mechanics', 'products'));
    }

    /**
     * Store a standalone job (creates a service order automatically).
     */
    public function storeStandalone(Request $request)
    {
        $validated = $request->validate([
            'job_type_id' => 'required|exists:job_types,id',
            'mechanic_id' => 'required|exists:mechanics,id',
            'labor_cost' => 'required|numeric|min:0',
            'description' => 'required|string',
            'vehicle_info' => 'required|string',
            'customer_name' => 'required|string',
            'customer_phone' => 'nullable|string',
            'products' => 'nullable|array',
            'products.*.id' => 'exists:products,id',
            'products.*.quantity' => 'integer|min:1',
        ]);

        try {
            $inventoryService = app(InventoryService::class);

            $job = DB::transaction(function () use ($validated, $request, $inventoryService) {
                $serviceOrder = ServiceOrder::create([
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'] ?? 'N/A',
                    'vehicle_info' => $validated['vehicle_info'],
                    'service_description' => $validated['description'],
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);

                $job = new WorkshopJob([
                    'job_type_id' => $validated['job_type_id'],
                    'mechanic_id' => $validated['mechanic_id'],
                    'labor_cost' => $validated['labor_cost'],
                    'description' => $validated['description'],
                    'customer_name' => $serviceOrder->customer_name,
                    'customer_phone' => $serviceOrder->customer_phone,
                    'vehicle_info' => $serviceOrder->vehicle_info,
                    'status' => 'pending',
                    'started_at' => now(),
                ]);
                $job->service_order_id = $serviceOrder->id;
                $job->save();

                $this->attachJobProductsFromInventory(
                    $job,
                    $request->input('products', []),
                    $inventoryService,
                    "Trabajo Individual #{$job->id}"
                );

                return $job;
            });
        } catch (InsufficientStockException $exception) {
            return back()->withErrors([
                'products' => $exception->getMessage(),
            ])->withInput();
        }

        return redirect()->route('jobs.index')->with('success', 'Trabajo creado correctamente.');
    }

    public function store(Request $request, ServiceOrder $serviceOrder)
    {
        if (!$serviceOrder->canReceiveJobs()) {
            return back()->withErrors([
                'service_order' => 'No se pueden agregar trabajos a una orden completada o cancelada.',
            ])->withInput();
        }

        $validated = $request->validate([
            'job_type_id' => 'required|exists:job_types,id',
            'mechanic_id' => 'required|exists:mechanics,id',
            'labor_cost' => 'required|numeric|min:0',
            'description' => 'required|string',
            'products' => 'nullable|array',
            'products.*.id' => 'exists:products,id',
            'products.*.quantity' => 'integer|min:1',
        ]);

        try {
            $inventoryService = app(InventoryService::class);

            $job = DB::transaction(function () use ($validated, $serviceOrder, $request, $inventoryService) {
                $job = new WorkshopJob($validated);
                $job->service_order_id = $serviceOrder->id;
                
                $job->customer_name = $serviceOrder->customer_name;
                $job->customer_phone = $serviceOrder->customer_phone ?? 'N/A';
                $job->vehicle_info = $serviceOrder->vehicle_info;
                
                $job->save();

                $this->attachJobProductsFromInventory(
                    $job,
                    $request->input('products', []),
                    $inventoryService,
                    "Trabajo #{$job->id} - Orden #{$serviceOrder->id}"
                );

                return $job;
            });
        } catch (InsufficientStockException $exception) {
            return back()->withErrors([
                'products' => $exception->getMessage(),
            ])->withInput();
        }

        if ($serviceOrder->status === 'pending') {
            $serviceOrder->update(['status' => 'in_progress']);
        }

        return redirect()->route('service-orders.show', $serviceOrder)->with('success', 'Trabajo añadido con éxito.');
    }

    /**
     * Remove a workshop job and restore consumed product stock.
     * Fix A05: Previously deleted without reversing inventory movements.
     */
    public function destroy(WorkshopJob $job)
    {
        $serviceOrder = $job->serviceOrder;

        DB::transaction(function () use ($job) {
            // Restaurar stock de productos consumidos antes de eliminar
            foreach ($job->jobProducts as $jobProduct) {
                $product = \App\Models\Product::withTrashed()
                    ->whereKey($jobProduct->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $product->increment('stock', $jobProduct->quantity);

                    InventoryMovement::create([
                        'product_id'    => $jobProduct->product_id,
                        'movement_type' => 'reversal',
                        'quantity'      => $jobProduct->quantity,
                        'unit_price'    => $jobProduct->unit_price,
                        'reference'     => "Eliminacion Trabajo #{$job->id}",
                        'movement_date' => now(),
                    ]);
                }
            }

            $job->delete();
        });

        return redirect()->route('service-orders.show', $serviceOrder)->with('success', 'Trabajo eliminado y stock restaurado.');
    }

    public function complete(WorkshopJob $job)
    {
        if (!$job->canTransitionTo(WorkshopJob::STATUS_COMPLETED)) {
            return back()->with('error', 'Este trabajo no puede marcarse como completado desde su estado actual.');
        }

        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Trabajo marcado como completado.');
    }

    private function attachJobProductsFromInventory(
        WorkshopJob $job,
        array $products,
        InventoryService $inventoryService,
        string $reference
    ): void {
        foreach ($products as $productData) {
            $tramos = $inventoryService->deductStock(
                $productData['id'],
                $productData['quantity'],
                $reference,
                'job_usage'
            );

            foreach ($tramos as $tramo) {
                $job->jobProducts()->create([
                    'product_id' => $productData['id'],
                    'quantity' => $tramo['quantity'],
                    'unit_price' => $tramo['unit_price'],
                    'total_price' => $tramo['unit_price'] * $tramo['quantity'],
                ]);
            }
        }
    }
}
