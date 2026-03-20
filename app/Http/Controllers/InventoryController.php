<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\Supplier;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $query = InventoryMovement::with(['product', 'supplier']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('supplier', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('movement_type', $request->input('type'));
        }

        if ($request->filled('date_start')) {
            $query->whereDate('movement_date', '>=', $request->input('date_start'));
        }

        if ($request->filled('date_end')) {
            $query->whereDate('movement_date', '<=', $request->input('date_end'));
        }

        $movements = $query->latest('movement_date')->latest('id')->paginate(10)->appends($request->all());
        return view('inventory.index', compact('movements'));
    }

    public function createPurchase()
    {
        $products = Product::with(['suppliers' => function($query) {
            $query->where('active', true);
        }])->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        return view('inventory.purchase', compact('products', 'suppliers'));
    }

    public function storePurchase(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => [
                'required',
                'exists:suppliers,id,active,1',
                function ($attribute, $value, $fail) use ($request) {
                    $product = \App\Models\Product::find($request->product_id);
                    if ($product && !$product->suppliers()->where('suppliers.id', $value)->exists()) {
                        $fail('El proveedor seleccionado no está asociado a este producto.');
                    }
                },
            ],
            'quantity'   => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'reference'  => 'nullable|string',
            'notes'      => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        
        // ANTES:
        /*
        $product->incrementStock(
            $validated['quantity'], 
            $validated['unit_price'], 
            $validated['supplier_id'],
            $validated['reference']
        );
        */

        // DESPUÉS:
        $inventoryService = app(\App\Services\InventoryService::class);
        $inventoryService->registerPurchaseBatch([
            'product_id'    => $validated['product_id'],
            'supplier_id'   => $validated['supplier_id'],
            'quantity'      => $validated['quantity'],
            'cost_price'    => $validated['unit_price'],
            'sale_price'    => isset($validated['sale_price']) ? $validated['sale_price'] : $product->sale_price,
            'selling_price' => isset($validated['sale_price']) ? $validated['sale_price'] : $product->sale_price,
            'reference'     => $validated['reference'],
            'purchased_at'  => now(),
        ]);

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
