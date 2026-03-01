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
        $sales = Sale::with(['user', 'saleProducts.product'])
            ->latest()
            ->paginate(10);
            
        $todayTotal = Sale::whereDate('created_at', today())
            ->where('status', '!=', 'anulada')
            ->sum('total_amount');
        $todayCount = Sale::whereDate('created_at', today())
            ->where('status', '!=', 'anulada')
            ->count();
            
        return view('sales.index', compact('sales', 'todayTotal', 'todayCount'));
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
            'customer_name' => 'nullable|string|max:255',
            'payment_method' => 'required|string|in:Efectivo,Transferencia,Tarjeta,Otro',
        ]);

        $product = \App\Models\Product::findOrFail($validated['product_id']);
        
        $total = $product->sale_price * $validated['quantity'];
        
        $sale = \App\Models\Sale::create([
            'customer_name' => $validated['customer_name'] ?? 'Venta Mostrador',
            'total_amount' => $total,
            'sale_date' => now(),
            'payment_method' => $validated['payment_method'],
            'user_id' => auth()->id(),
            'status' => 'completada',
        ]);

        // Guardar el producto en sale_products (esto faltaba en el store original o se hacía a medias)
        $sale->saleProducts()->create([
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'unit_price' => $product->sale_price,
            'total_price' => $total,
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

    /**
     * Cancel the specified resource.
     */
    public function cancel(Sale $sale)
{
    if ($sale->status === 'anulada') {
        return back()->with('error', 'Esta venta ya ha sido anulada.');
    }

    $sale->update(['status' => 'anulada']);

    foreach($sale->saleProducts as $item) {
        $item->product->incrementStock(
            $item->quantity,        // cantidad
            null,                   // unitPrice: null, no cambia purchase_price
            null,                   // supplierId: null
            "Anulación Venta #{$sale->id}"  // reference ✅
        );
    }

    return redirect()->route('sales.index')->with('success', 'Venta anulada y stock devuelto.');
}
}
