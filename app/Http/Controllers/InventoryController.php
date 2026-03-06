<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\Supplier;

class InventoryController extends Controller
{
    public function index()
    {
        $movements = InventoryMovement::with(['product', 'supplier'])->latest()->paginate(10);
        return view('inventory.index', compact('movements'));
    }

    public function createPurchase()
    {
        $products = Product::with('suppliers')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('inventory.purchase', compact('products', 'suppliers'));
    }

    public function storePurchase(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
                function ($attribute, $value, $fail) use ($request) {
                    $product = \App\Models\Product::find($request->product_id);
                    if ($product && !$product->suppliers()->where('suppliers.id', $value)->exists()) {
                        $fail('El proveedor seleccionado no está asociado a este producto.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        
        $product->incrementStock(
            $validated['quantity'], 
            $validated['unit_price'], 
            $validated['supplier_id'],
            $validated['reference']
        );

        return redirect()->route('inventory.index')->with('success', 'Compra registrada y stock actualizado.');
    }

    public function createAdjustment()
    {
        $products = Product::all();
        return view('inventory.adjustment', compact('products'));
    }

    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'reason' => 'required|in:adjustment,damage,loss',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        
        if ($validated['quantity'] > 0) {
            $product->incrementStock($validated['quantity'], null, null, 'adjustment');
        } else {
            $product->decrementStock(abs($validated['quantity']), 'adjustment');
        }

        return redirect()->route('inventory.index')->with('success', 'Ajuste de inventario realizado.');
    }
}
