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
    public function index()
    {
        $serviceOrders = ServiceOrder::all();
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
            'customer_phone' => 'nullable|string|max:20',
            'vehicle_info' => 'required|string',
            'service_description' => 'required|string',
        ]);

        ServiceOrder::create($validated);

        return redirect()->route('service-orders.index')->with('success', 'Orden de servicio creada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceOrder $serviceOrder)
    {
        return view('service-orders.show', compact('serviceOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceOrder $serviceOrder)
    {
        $mechanics = Mechanic::all();
        return view('service-orders.edit', compact('serviceOrder', 'mechanics'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'vehicle_plate' => 'required|string|max:10',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            // ... otros campos
        ]);

        $serviceOrder->update($validated);

        return redirect()->route('service-orders.index')->with('success', 'Orden actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceOrder $serviceOrder)
    {
        $serviceOrder->delete();
        return redirect()->route('service-orders.index')->with('success', 'Orden eliminada.');
    }
}
