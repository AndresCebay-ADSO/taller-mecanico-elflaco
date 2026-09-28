<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Sale;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    private const PAYMENT_METHODS = 'Efectivo,Nequi,Daviplata,Transferencia,Tarjeta,Otro';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branchId = $this->currentBranchId();
        $request->validate([
            'search' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|in:' . self::PAYMENT_METHODS,
            'status' => 'nullable|string|in:completada,anulada',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $query = Sale::with(['saleProducts' => function ($query) {
            $query->with(['product' => function ($q) {
                $q->withTrashed();
            }]);
        }, 'user'])->forBranch($branchId);

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }

                $q->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhereHas('saleProducts.product', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_start')) {
            $query->whereDate('sale_date', '>=', $request->input('date_start'));
        }

        if ($request->filled('date_end')) {
            $query->whereDate('sale_date', '<=', $request->input('date_end'));
        }

        $sales = $query->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($request->all());

        $todayTotal = Sale::forBranch($branchId)
            ->whereDate('sale_date', today())
            ->where('status', '!=', 'anulada')
            ->sum('total_amount');

        $todayCount = Sale::forBranch($branchId)
            ->whereDate('sale_date', today())
            ->where('status', '!=', 'anulada')
            ->count();

        $todayByMethod = Sale::forBranch($branchId)
            ->select('payment_method', DB::raw('SUM(total_amount) as total'))
            ->whereDate('sale_date', today())
            ->where('status', '!=', 'anulada')
            ->groupBy('payment_method')
            ->having('total', '>', 0)
            ->get();

        return view('sales.index', compact('sales', 'todayTotal', 'todayCount', 'todayByMethod'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(InventoryService $inventoryService)
    {
        $products = $inventoryService->sellableProducts($this->currentBranchId());

        return view('sales.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SaleService $saleService)
    {
        $branchId = $this->requireCurrentBranchId();

        $validated = $request->validate([
            'customer_name'       => 'nullable|string|max:255',
            'payment_method'      => 'required|string|in:' . self::PAYMENT_METHODS,
            'products'            => 'required|array|min:1',
            'products.*.id'       => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'products.*.quantity' => 'required|integer|min:1|max:100000',
        ], [
            'products.required'            => 'Debes agregar al menos un producto.',
            'products.*.id.required'       => 'Selecciona un producto en cada fila.',
            'products.*.id.distinct'       => 'No puedes agregar el mismo producto dos veces.',
            'products.*.id.exists'         => 'Uno de los productos seleccionados no es válido.',
            'products.*.quantity.required' => 'La cantidad es obligatoria.',
            'products.*.quantity.min'      => 'La cantidad mínima es 1.',
            'payment_method.required'      => 'El método de pago es obligatorio.',
        ]);

        try {
            $result = $saleService->create(
                $branchId,
                auth()->id(),
                $validated['customer_name'] ?? null,
                $validated['payment_method'],
                $validated['products']
            );
        } catch (InsufficientStockException|BusinessRuleException $exception) {
            return back()->withErrors(['products' => $exception->getMessage()])->withInput();
        }

        if ($result['low_stock']) {
            session()->flash('show_low_stock_toast', true);
        }

        return redirect()->route('sales.index')->with('success', 'Venta registrada y stock descontado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        $this->ensureCurrentBranch($sale->branch_id);

        $sale->load(['saleProducts.product' => function ($q) {
            $q->withTrashed();
        }]);

        return view('sales.show', compact('sale'));
    }

    /**
     * Cancel the specified sale and return its stock to the original batches.
     */
    public function cancel(Sale $sale, SaleService $saleService)
    {
        $this->ensureCurrentBranch($sale->branch_id);

        if (!$saleService->cancel($sale)) {
            return back()->with('error', 'Esta venta ya ha sido anulada.');
        }

        return redirect()->route('sales.index')->with('success', 'Venta anulada y stock devuelto.');
    }
}
