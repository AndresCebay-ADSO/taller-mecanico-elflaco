<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\BranchService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branchId = $this->currentBranchId();
        $query = Product::withTrashed()->with('suppliers')->forBranch($branchId);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('upc', 'like', "%{$search}%")
                  ->orWhereHas('suppliers', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Low stock filter (only applies to active products)
        if ($request->has('low_stock')) {
            $query->whereNull('deleted_at')->whereColumn('stock', '<=', 'min_stock');
        }

        // Active products first, soft-deleted at the end
        $products = $query
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
            ->latest()
            ->paginate(10)
            ->appends($request->all());

        // Get unique categories for the filter (active products only)
        $categories = Product::forBranch($branchId)->distinct()->pluck('category')->sort();

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(BranchService $branchService)
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $currentBranch = $branchService->getCurrentBranch();
        $categories = $this->categories();

        return view('products.create', compact('suppliers', 'currentBranch', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request, InventoryService $inventoryService)
    {
        $branchId = $this->requireCurrentBranchId();
        $validated = $request->validated();

        $supplierIds = $validated['supplier_ids'];
        $initialStock = (int) $validated['stock'];
        $initialSupplierId = $validated['initial_supplier_id'] ?? null;
        $purchasePrice = $validated['purchase_price'] ?? 0;

        unset($validated['supplier_ids'], $validated['initial_supplier_id']);

        DB::transaction(function () use ($validated, $branchId, $supplierIds, $initialStock, $initialSupplierId, $purchasePrice, $inventoryService) {
            // El stock siempre nace en 0: solo los lotes pueden aumentarlo.
            $product = Product::create([
                ...$validated,
                'purchase_price' => $purchasePrice,
                'stock' => 0,
                'branch_id' => $branchId,
            ]);
            $product->suppliers()->sync($supplierIds);

            if ($initialStock > 0) {
                $inventoryService->registerPurchaseBatch([
                    'product_id' => $product->id,
                    'supplier_id' => $initialSupplierId,
                    'quantity' => $initialStock,
                    'cost_price' => $purchasePrice,
                    'sale_price' => $product->sale_price,
                    'reference' => 'Carga Inicial',
                    'purchased_at' => now(),
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $this->ensureCurrentBranch($product->branch_id);

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $this->ensureCurrentBranch($product->branch_id);

        $suppliers = Supplier::active()->orderBy('name')->get();
        $selectedSupplierIds = $product->suppliers()->pluck('suppliers.id')->toArray();
        $categories = $this->categories();

        return view('products.edit', compact('product', 'suppliers', 'selectedSupplierIds', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $this->ensureCurrentBranch($product->branch_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
            // Sin costo: el costo de referencia cambia solo con las compras (decisión 6).
            'sale_price' => 'required|numeric|min:0|max:99999999',
            'min_stock' => 'required|integer|min:0|max:100000',
            'upc' => 'nullable|string|max:50',
        ]);

        $supplierIds = $validated['supplier_ids'];
        unset($validated['supplier_ids']);
        $priceChanged = (float) $validated['sale_price'] !== (float) $product->sale_price;

        DB::transaction(function () use ($product, $validated, $supplierIds, $priceChanged) {
            $product->update($validated);
            $product->suppliers()->sync($supplierIds);

            // En la venta se cobra el precio de cada lote: si cambia el precio del producto,
            // las unidades que quedan en inventario pasan a venderse al precio nuevo.
            if ($priceChanged) {
                $product->batches()->withStock()->update(['sale_price' => $validated['sale_price']]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete if the product has history, otherwise force delete.
     */
    public function destroy(Product $product)
    {
        $this->ensureCurrentBranch($product->branch_id);

        $hasHistory = $product->inventoryMovements()->exists()
                   || $product->saleProducts()->exists()
                   || $product->jobProducts()->exists()
                   || $product->batches()->exists();

        // Desactivarlo escondería esas unidades de ventas, trabajos y del valor del inventario.
        if ($product->stock > 0) {
            return redirect()->route('products.index')->with('error',
                "«{$product->name}» todavía tiene {$product->stock} unidades. Sácalas con un ajuste de inventario antes de desactivarlo.");
        }

        if ($hasHistory) {
            $product->delete(); // soft delete
            return redirect()->route('products.index')
                ->with('info', 'El producto fue desactivado porque tiene historial de movimientos. Puede reactivarlo desde el listado.');
        }

        $product->forceDelete();
        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(int $id)
    {
        $product = Product::withTrashed()->forBranch($this->currentBranchId())->findOrFail($id);
        $product->restore();

        return redirect()->route('products.index')
            ->with('success', 'Producto reactivado exitosamente.');
    }

    /**
     * Categorías ya usadas en la sucursal, para sugerirlas. La categoría es texto libre: cada taller usa las suyas.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function categories()
    {
        return Product::forBranch($this->currentBranchId())->whereNotNull('category')
            ->distinct()->orderBy('category')->pluck('category');
    }
}
