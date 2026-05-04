<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\BranchService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use RuntimeException;

class InventoryController extends Controller
{
    public function index(Request $request, BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $query = InventoryMovement::with(['product' => function ($query) {
            $query->withTrashed();
        }, 'supplier', 'batch'])
            ->whereHas('product', function ($q) use ($branchId) {
                $q->forBranch($branchId);
            });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                    $supplierQuery->where('name', 'like', "%{$search}%");
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
        $activeSuppliers = Supplier::active()->orderBy('name')->get();

        return view('inventory.index', compact('movements', 'activeSuppliers'));
    }

    public function createPurchase(BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $products = Product::forBranch($branchId)->with(['suppliers' => function ($query) {
            $query->where('active', true);
        }])->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('inventory.purchase', compact('products', 'suppliers'));
    }

    public function storePurchase(Request $request, BranchService $branchService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => [
                'required',
                'exists:suppliers,id,active,1',
                function ($attribute, $value, $fail) use ($request) {
                    $product = Product::find($request->product_id);
                    if ($product && !$product->suppliers()->where('suppliers.id', $value)->exists()) {
                        $fail('El proveedor seleccionado no esta asociado a este producto.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $product = Product::forBranch($branchService->getCurrentBranch()?->id)->findOrFail($validated['product_id']);
        $inventoryService = app(InventoryService::class);

        $inventoryService->registerPurchaseBatch([
            'product_id' => $validated['product_id'],
            'supplier_id' => $validated['supplier_id'],
            'quantity' => $validated['quantity'],
            'cost_price' => $validated['unit_price'],
            'sale_price' => $validated['sale_price'] ?? $product->sale_price,
            'selling_price' => $validated['sale_price'] ?? $product->sale_price,
            'reference' => $validated['reference'],
            'purchased_at' => now(),
        ]);

        return redirect()->route('inventory.index')->with('success', 'Compra registrada y stock actualizado.');
    }

    public function createAdjustment(BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $products = Product::forBranch($branchId)->get();
        return view('inventory.adjustment', compact('products'));
    }

    public function storeAdjustment(Request $request, BranchService $branchService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|in:adjustment,damage,loss',
            'notes' => 'nullable|string',
        ]);

        $product = Product::forBranch($branchService->getCurrentBranch()?->id)->findOrFail($validated['product_id']);

        try {
            app(InventoryService::class)->adjustStock(
                $product->id,
                $validated['quantity'],
                $validated['reason'],
                $validated['notes'] ?? null
            );
        } catch (InsufficientStockException|RuntimeException $exception) {
            return back()->withErrors([
                'quantity' => $exception->getMessage(),
            ])->withInput();
        }

        return redirect()->route('inventory.index')->with('success', 'Ajuste de inventario realizado.');
    }
}
