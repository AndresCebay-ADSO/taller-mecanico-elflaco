<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceOrder;
use App\Models\Mechanic;

class ServiceOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $query = ServiceOrder::query()

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
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|digits:10',
            'vehicle_info' => 'required|string',
            'service_description' => 'required|string',
        ]);

        ServiceOrder::create($validated);

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden de servicio creada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceOrder $serviceOrder)
    {
        $mechanics = Mechanic::where('is_active', true)->get();
        $jobTypes = \App\Models\JobType::where('is_active', true)->get();
        $products = \App\Models\Product::where('stock', '>', 0)->get();

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

        $serviceOrder->update($validated);

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceOrder $serviceOrder)
    {
        $serviceOrder->delete();

        return redirect()
            ->route('service-orders.index')
            ->with('success', 'Orden eliminada.');
    }
}