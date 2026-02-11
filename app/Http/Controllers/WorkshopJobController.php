<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Models\JobType;
use App\Models\Mechanic;

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

        $job = \DB::transaction(function () use ($validated, $request) {
            // 1. Crear la Orden de Servicio
            $serviceOrder = ServiceOrder::create([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? 'N/A',
                'vehicle_info' => $validated['vehicle_info'],
                'service_description' => $validated['description'],
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            // 2. Crear el Trabajo
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

            // 3. Procesar Productos
            if ($request->has('products')) {
                foreach ($request->products as $pData) {
                    $product = \App\Models\Product::findOrFail($pData['id']);
                    
                    $job->jobProducts()->create([
                        'product_id' => $product->id,
                        'quantity' => $pData['quantity'],
                        'unit_price' => $product->sale_price,
                        'total_price' => $product->sale_price * $pData['quantity'],
                    ]);

                    $product->decrementStock($pData['quantity'], 'job', "Trabajo Individual #{$job->id}");
                }
            }

            return $job;
        });

        return redirect()->route('jobs.index')->with('success', 'Trabajo creado correctamente.');
    }

    public function store(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'job_type_id' => 'required|exists:job_types,id',
            'mechanic_id' => 'required|exists:mechanics,id',
            'labor_cost' => 'required|numeric|min:0',
            'description' => 'required|string',
            'products' => 'nullable|array',
            'products.*.id' => 'exists:products,id',
            'products.*.quantity' => 'integer|min:1',
        ]);

        $job = \DB::transaction(function () use ($validated, $serviceOrder, $request) {
            $job = new WorkshopJob($validated);
            $job->service_order_id = $serviceOrder->id;
            
            // Copiar info del cliente de la orden
            $job->customer_name = $serviceOrder->customer_name;
            $job->customer_phone = $serviceOrder->customer_phone ?? 'N/A';
            $job->vehicle_info = $serviceOrder->vehicle_info;
            
            $job->save();

            // Procesar Productos
            if ($request->has('products')) {
                foreach ($request->products as $pData) {
                    $product = \App\Models\Product::findOrFail($pData['id']);
                    
                    $job->jobProducts()->create([
                        'product_id' => $product->id,
                        'quantity' => $pData['quantity'],
                        'unit_price' => $product->sale_price,
                        'total_price' => $product->sale_price * $pData['quantity'],
                    ]);

                    // Descontar stock con trazabilidad
                    $product->decrementStock($pData['quantity'], 'job', "Trabajo #{$job->id} - Orden #{$serviceOrder->id}");
                }
            }

            return $job;
        });

        if ($serviceOrder->status === 'pending') {
            $serviceOrder->update(['status' => 'in_progress']);
        }

        return redirect()->route('service-orders.show', $serviceOrder)->with('success', 'Trabajo añadido con éxito.');
    }

    public function destroy(WorkshopJob $job)
    {
        $serviceOrder = $job->serviceOrder;
        $job->delete();
        
        return redirect()->route('service-orders.show', $serviceOrder)->with('success', 'Trabajo eliminado.');
    }

    public function complete(WorkshopJob $job)
    {
        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Trabajo marcado como completado.');
    }
}
