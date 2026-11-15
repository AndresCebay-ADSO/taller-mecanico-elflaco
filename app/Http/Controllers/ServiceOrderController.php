<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\JobType;
use App\Services\CashService;
use App\Services\InventoryService;
use App\Services\WorkshopJobService;

class ServiceOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branchId = $this->currentBranchId();
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
    public function store(Request $request)
    {
        $branchId = $this->requireCurrentBranchId();

        $validated = $request->validate(ServiceOrder::FORM_RULES);

        $validated['branch_id'] = $branchId;

        ServiceOrder::create($validated);

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden de servicio creada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceOrder $serviceOrder, InventoryService $inventoryService, CashService $cashService)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);
        $branchId = $serviceOrder->branch_id;

        $serviceOrder->load(['invoices', 'workshopJobs.mechanic', 'workshopJobs.jobType', 'workshopJobs.jobProducts.product' => function ($q) {
            $q->withTrashed();
        }]);

        $mechanics = Mechanic::forBranch($branchId)->where('is_active', true)->orderBy('name')->get();
        $jobTypes = JobType::where('is_active', true)->orderBy('name')->get();
        $products = $inventoryService->sellableProducts($branchId);
        $cashClosed = $cashService->closedToday($branchId);

        return view(
            'service-orders.show',
            compact('serviceOrder', 'mechanics', 'jobTypes', 'products', 'cashClosed')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceOrder $serviceOrder)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);

        return view('service-orders.edit', compact('serviceOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceOrder $serviceOrder, WorkshopJobService $jobService)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);

        $validated = $request->validate(ServiceOrder::FORM_RULES + [
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $newStatus = $validated['status'];
        $statusChanges = $newStatus !== $serviceOrder->status;

        if (!$serviceOrder->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'La transicion de estado solicitada no es valida para esta orden.',
            ])->withInput();
        }

        // Una orden solo se completa generando su factura: así ningún cobro queda fuera de la caja (decisión 12).
        if ($statusChanges && $newStatus === ServiceOrder::STATUS_COMPLETED) {
            return back()->withErrors([
                'status' => 'La orden se completa al generar su factura, desde el detalle de la orden.',
            ])->withInput();
        }

        $details = Arr::except($validated, ['status']);

        // Cancelar devuelve al inventario los repuestos de sus trabajos.
        if ($statusChanges && $newStatus === ServiceOrder::STATUS_CANCELLED) {
            try {
                $jobService->cancelOrder($serviceOrder);
            } catch (BusinessRuleException $exception) {
                return back()->withErrors([$exception->field => $exception->getMessage()])->withInput();
            }

            $serviceOrder->refresh()->update($details);

            return redirect()
                ->route('service-orders.show', $serviceOrder)
                ->with('success', 'Orden cancelada. Los repuestos de sus trabajos volvieron al inventario.');
        }

        $serviceOrder->update($details + [
            'status' => $newStatus,
            'completed_at' => $newStatus === ServiceOrder::STATUS_COMPLETED ? ($serviceOrder->completed_at ?? now()) : null,
        ]);

        return redirect()
            ->route('service-orders.show', $serviceOrder)
            ->with('success', 'Orden actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     * Prevents deletion if jobs exist — protects stock traceability.
     */
    public function destroy(ServiceOrder $serviceOrder)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);

        if ($serviceOrder->workshopJobs()->exists()) {
            return back()->with('error', 'No se puede eliminar una orden con trabajos asociados. Cancélela primero.');
        }

        $serviceOrder->delete();

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden eliminada.');
    }
}
