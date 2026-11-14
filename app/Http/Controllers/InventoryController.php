<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\CashService;
use App\Services\InventoryService;
use App\Support\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->currentBranchId();
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
                $q->withTrashed()->forBranch($branchId);
            });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->withTrashed()->where('name', 'like', "%{$search}%");
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

    public function createPurchase()
    {
        $products = Product::forBranch($this->currentBranchId())->with(['suppliers' => function ($query) {
            $query->where('active', true);
        }])->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('inventory.purchase', compact('products', 'suppliers'));
    }

    public function storePurchase(Request $request, InventoryService $inventoryService, CashService $cashService)
    {
        $branchId = $this->requireCurrentBranchId();

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('active', true),
                function ($attribute, $value, $fail) use ($request) {
                    $product = Product::find($request->product_id);
                    if ($product && !$product->suppliers()->where('suppliers.id', $value)->exists()) {
                        $fail('El proveedor seleccionado no esta asociado a este producto.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:1|max:100000',
            'unit_price' => 'required|numeric|min:0|max:99999999',
            'sale_price' => 'nullable|numeric|min:0|max:99999999',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'cash_payment' => 'required|in:none,' . implode(',', PaymentMethod::ALL),
        ], [
            'cash_payment.required' => 'Indica si la compra se pagó con la plata de la caja.',
        ]);

        $product = Product::forBranch($branchId)->findOrFail($validated['product_id']);

        // La compra y su salida de caja van juntas: si la caja de hoy ya está cerrada, no se registra ninguna.
        try {
            DB::transaction(function () use ($inventoryService, $cashService, $validated, $product, $branchId) {
                $batch = $inventoryService->registerPurchaseBatch([
                    'product_id' => $product->id,
                    'supplier_id' => $validated['supplier_id'],
                    'quantity' => $validated['quantity'],
                    'cost_price' => $validated['unit_price'],
                    'sale_price' => $validated['sale_price'] ?? $product->sale_price,
                    'reference' => $validated['reference'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'purchased_at' => now(),
                ]);

                if ($validated['cash_payment'] !== 'none' && (float) $batch->cost_price * $batch->quantity > 0) {
                    $cashService->recordPurchase($branchId, $batch, $validated['cash_payment'], auth()->id());
                }
            });
        } catch (BusinessRuleException $exception) {
            return back()->withInput()->with('error', $exception->getMessage() . ' Reábrela o registra la compra como pagada con otra plata.');
        }

        return redirect()->route('inventory.index')->with('success', $validated['cash_payment'] === 'none'
            ? 'Compra registrada y stock actualizado.'
            : 'Compra registrada, stock actualizado y descontada de la caja de hoy.');
    }

    public function createAdjustment()
    {
        $products = Product::forBranch($this->currentBranchId())->orderBy('name')->get();

        return view('inventory.adjustment', compact('products'));
    }

    public function storeAdjustment(Request $request, InventoryService $inventoryService)
    {
        $branchId = $this->requireCurrentBranchId();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|not_in:0|min:-100000|max:100000',
            'reason' => 'required|in:adjustment,damage,loss',
            'notes' => 'nullable|string|max:1000',
        ]);

        $product = Product::forBranch($branchId)->findOrFail($validated['product_id']);

        try {
            $inventoryService->adjustStock(
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
