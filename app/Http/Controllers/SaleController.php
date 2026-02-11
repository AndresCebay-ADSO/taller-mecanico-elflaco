<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Sale;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = Sale::all();
        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = \App\Models\Product::where('stock', '>', 0)->get();
        return view('sales.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = \App\Models\Product::findOrFail($validated['product_id']);
        
        $total = $product->sale_price * $validated['quantity'];
        
        $sale = \App\Models\Sale::create([
            'customer_name' => $request->customer_name ?? 'Cliente General',
            'total_amount' => $total,
            'sale_date' => now(),
        ]);

        $product->decrementStock($validated['quantity'], 'sale', "Venta #{$sale->id}");

        return redirect()->route('sales.index')->with('success', 'Venta registrada y stock descontado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        return view('sales.edit', compact('sale'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:0',
        ]);

        $sale->update($validated);

        return redirect()->route('sales.index')->with('success', 'Venta actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'Venta eliminada.');
    }
}
