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
        $serviceOrders = ServiceOrder::with('mechanic')->get();
        return view('service_orders.index', compact('serviceOrders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $mechanics = Mechanic::where('is_active', true)->get();
        return view('service_orders.create', compact('mechanics'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_plate' => 'required|string|max:10',
            'customer_name' => 'required|string|max:255',
            'description' => 'required|string',
            'mechanic_id' => 'required|exists:mechanics,id',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'entry_date' => 'required|date',
        ]);

        ServiceOrder::create($validated);

        return redirect()->route('service-orders.index')->with('success', 'Orden de servicio creada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceOrder $serviceOrder)
    {
        return view('service_orders.show', compact('serviceOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceOrder $serviceOrder)
    {
        $mechanics = Mechanic::all();
        return view('service_orders.edit', compact('serviceOrder', 'mechanics'));
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
