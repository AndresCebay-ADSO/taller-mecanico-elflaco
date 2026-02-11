<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Invoice;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = Invoice::all();
        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('invoices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'service_order_id' => 'required|exists:service_orders,id',
        ]);

        Invoice::create($validated);

        return redirect()->route('invoices.index')->with('success', 'Factura generada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $invoice->update($validated);

        return redirect()->route('invoices.index')->with('success', 'Factura actualizada.');
    }

    /**
     * Generate an invoice from a Service Order.
     */
    public function generateFromServiceOrder(\App\Models\ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->invoices()->exists()) {
            return back()->with('error', 'Esta orden ya tiene una factura vinculada.');
        }

        $totalLabor = $serviceOrder->workshopJobs->sum('labor_cost');
        $totalProducts = $serviceOrder->workshopJobs->sum(fn($j) => $j->jobProducts->sum('total_price'));
        $totalAmount = $totalLabor + $totalProducts;

        $invoice = Invoice::create([
            'invoice_number' => 'FAC-' . strtoupper(uniqid()),
            'service_order_id' => $serviceOrder->id,
            'amount' => $totalAmount,
            'invoice_date' => now(),
        ]);

        $serviceOrder->update(['status' => 'completed']);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Factura consolidada generada con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Factura eliminada.');
    }
}
