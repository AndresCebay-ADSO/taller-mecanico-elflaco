<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Supplier;
use App\Http\Requests\StoreProductRequest;
use App\Services\InventoryService;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with('suppliers');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('upc', 'like', "%{$search}%")
                  ->orWhereHas('suppliers', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Low stock filter (Optional but recommended since it was in the UI)
        if ($request->has('low_stock')) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        $products = $query->latest()->paginate(10)->appends($request->all());
        
        // Get unique categories for the filter
        $categories = Product::distinct()->pluck('category')->sort();

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('products.create', compact('suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $supplierIds = $validated['supplier_ids'];
        unset($validated['supplier_ids']);

        // Extraer los campos de stock inicial para el lote
        $stockInicial = (int) $validated['stock'];
        $initialSupplierId = $validated['initial_supplier_id'] ?? null;
        $purchasePrice = $validated['purchase_price'] ?? 0;
        
        unset($validated['initial_supplier_id']);

        // Crear el producto
        $product = Product::create($validated);
        $product->suppliers()->sync($supplierIds);

        // Si hay stock inicial, registrar el lote a través del servicio
        if ($stockInicial > 0) {
            $inventoryService = app(InventoryService::class);
            
            // Ponemos el stock del producto en 0 temporalmente para que registerPurchaseBatch 
            // lo incremente correctamente y no se duplique lo que ya guardamos en Product::create
            $product->update(['stock' => 0]);

            $inventoryService->registerPurchaseBatch([
                'product_id'    => $product->id,
                'supplier_id'   => $initialSupplierId,
                'quantity'      => $stockInicial,
                'cost_price'    => $purchasePrice,
                'selling_price' => $product->sale_price,
                'reference'     => 'Carga Inicial',
                'purchased_at'  => now(),
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente con su lote de stock inicial.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $selectedSupplierIds = $product->suppliers()->pluck('suppliers.id')->toArray();
        return view('products.edit', compact('product', 'suppliers', 'selectedSupplierIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'upc' => 'nullable|string|max:50',
        ]);

        $supplierIds = $validated['supplier_ids'];
        unset($validated['supplier_ids']);

        $product->update($validated);
        $product->suppliers()->sync($supplierIds);

        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
