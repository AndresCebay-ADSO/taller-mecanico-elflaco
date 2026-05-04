<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\JobType;
use App\Services\BranchService;

class ServiceOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $query = ServiceOrder::forBranch($branchId)

            ->when($request->filled('search'), function ($q) use ($request) {

                $search = $request->search;

                $q->where(function ($sub) use ($search) {

                    if (is_numeric($search)) {
                        $sub->where('id', $search)
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    } else {
                        $sub->where('customer_name', 'like', "%{$search}%");
                    }

                });

            })

            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })

            ->when($request->date_start, function ($q, $date) {
                $q->whereDate('created_at', '>=', $date);
            })

            ->when($request->date_end, function ($q, $date) {
                $q->whereDate('created_at', '<=', $date);
            });

        $serviceOrders = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($request->only([
                'search',
                'status',
                'date_start',
                'date_end'
            ]));

        return view('service-orders.index', compact('serviceOrders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('service-orders.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, BranchService $branchService)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|digits:10',
            'vehicle_info' => 'required|string',
            'service_description' => 'required|string',
        ]);

        $validated['branch_id'] = $branchService->getCurrentBranch()?->id;

        ServiceOrder::create($validated);

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden de servicio creada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceOrder $serviceOrder, BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $serviceOrder->load(['workshopJobs.mechanic', 'workshopJobs.jobType', 'workshopJobs.jobProducts.product' => function($q) {
            $q->withTrashed();
        }]);

        $mechanics = Mechanic::forBranch($branchId)->where('is_active', true)->get();
        $jobTypes = JobType::where('is_active', true)->get();
        $products = Product::forBranch($branchId)->where('stock', '>', 0)->get();

        return view(
            'service-orders.show',
            compact('serviceOrder', 'mechanics', 'jobTypes', 'products')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceOrder $serviceOrder)
    {
        return view('service-orders.edit', compact('serviceOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|digits:10',
            'vehicle_info' => 'required|string',
            'service_description' => 'required|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        if (!$serviceOrder->canTransitionTo($validated['status'])) {
            return back()->withErrors([
                'status' => 'La transicion de estado solicitada no es valida para esta orden.',
            ])->withInput();
        }

        $validated['completed_at'] = $validated['status'] === 'completed'
            ? ($serviceOrder->completed_at ?? now())
            : null;

        $serviceOrder->update($validated);

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     * Fix M05: Prevents deletion if jobs exist — protects stock traceability.
     */
    public function destroy(ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->workshopJobs()->exists()) {
            return back()->with('error', 'No se puede eliminar una orden con trabajos asociados. Cancélela primero.');
        }

        $serviceOrder->delete();

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden eliminada.');
    }
}
