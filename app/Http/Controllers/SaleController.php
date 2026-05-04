<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Sale;
use App\Services\BranchService;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $request->validate([
            'search' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|in:Efectivo,Nequi,Daviplata,Transferencia,Tarjeta,Otro',
            'status' => 'nullable|string|in:completada,anulada',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $query = Sale::with(['saleProducts' => function($query) {
            $query->with(['product' => function($q) {
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
    public function create(BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $products = Product::forBranch($branchId)->where('stock', '>', 0)->get();
        return view('sales.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, BranchService $branchService)
    {
        $validated = $request->validate([
            'customer_name'       => 'nullable|string|max:255',
            'payment_method'      => 'required|string|in:Efectivo,Nequi,Daviplata,Transferencia,Tarjeta,Otro',
            'products'            => 'required|array|min:1',
            'products.*.id'       => 'required|distinct|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
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

            $hasLowStock = false;

            DB::transaction(function () use ($validated, &$hasLowStock, $branchService) {

                $productData = [];

                foreach ($validated['products'] as $index => $item) {

                    $id  = $item['id'];
                    $qty = $item['quantity'];

                    if (!isset($productData[$id])) {
                        $productData[$id] = [
                            'quantity' => 0,
                            'index' => $index
                        ];
                    }

                    $productData[$id]['quantity'] += $qty;
                }

                $sale = Sale::create([
                    'customer_name'  => $validated['customer_name'] ?? 'Venta Mostrador',
                    'total_amount'   => 0,
                    'branch_id'      => $branchService->getCurrentBranch()?->id,
                    'sale_date'      => now(),
                    'payment_method' => $validated['payment_method'],
                    'user_id'        => auth()->id(),
                    'status'         => 'completada',
                ]);

                $totalSaleAmount = 0;

                foreach ($productData as $productId => $data) {

                    $quantity = $data['quantity'];
                    $index    = $data['index'];

                    $product = Product::where('id', $productId)
                        ->where('branch_id', $branchService->getCurrentBranch()?->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($product->stock < $quantity) {

                        throw new \Illuminate\Validation\ValidationException(
                            validator([], []),
                            back()->withErrors([
                                "products.$index.quantity" =>
                                "Stock insuficiente para {$product->name}. Disponible: {$product->stock} unidades."
                            ])->withInput()
                        );

                    }

                    // Descontar stock FIFO — retorna un tramo por cada lote consumido (Opción A)
                    $inventoryService = app(\App\Services\InventoryService::class);
                    $tramos = $inventoryService->deductStock($product->id, $quantity, "Venta #{$sale->id}");

                    // Crear una línea en la venta por cada tramo (lote) consumido
                    foreach ($tramos as $tramo) {
                        $tramoTotal       = $tramo['unit_price'] * $tramo['quantity'];
                        $totalSaleAmount += $tramoTotal;

                        $sale->saleProducts()->create([
                            'product_id'  => $product->id,
                            'quantity'    => $tramo['quantity'],
                            'unit_price'  => $tramo['unit_price'],
                            'total_price' => $tramoTotal,
                        ]);
                    }

                    $product->refresh();
                    if ($product->stock <= $product->min_stock) {
                        $hasLowStock = true;
                    }
                }

                $sale->update([
                    'total_amount' => $totalSaleAmount
                ]);

            });

        } catch (\Illuminate\Validation\ValidationException $e) {

            return $e->getResponse();

        }

        if ($hasLowStock) {
            session()->flash('show_low_stock_toast', true);
        }

        return redirect()->route('sales.index')->with('success', 'Venta registrada y stock descontado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        $sale->load(['saleProducts.product' => function($q) {
            $q->withTrashed();
        }]);

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
     * Fix A03: Blocked direct total_amount edits to preserve financial traceability.
     */
    public function update(Request $request, Sale $sale)
    {
        if ($sale->status === 'anulada') {
            return back()->with('error', 'No se puede editar una venta anulada.');
        }

        abort(403, 'Las ventas completadas no pueden editarse directamente. Use Anular para corregir errores.');
    }

    /**
     * Remove the specified resource from storage.
     * Deshabilitado para preservar trazabilidad de stock
     */
    public function destroy(Sale $sale)
    {
        abort(403, 'Las ventas no pueden eliminarse directamente. Use Anular para revertir el stock.');
    }

    /**
     * Cancel the specified resource.
     */
    public function cancel(Sale $sale)
    {
        if ($sale->status === 'anulada') {
            return back()->with('error', 'Esta venta ya ha sido anulada.');
        }

        DB::transaction(function () use ($sale) {

            $sale->update([
                'status' => 'anulada'
            ]);

            // ANTES:
            /*
            foreach ($sale->saleProducts as $item) {
                $item->product->reverseStock(
                    $item->quantity,
                    "Anulación Venta #{$sale->id}"
                );
            }
            */

            // DESPUÉS:
            $inventoryService = app(\App\Services\InventoryService::class);
            $inventoryService->reverseStockFromSale($sale->id);

        });

        return redirect()->route('sales.index')->with('success', 'Venta anulada y stock devuelto.');
    }
}
