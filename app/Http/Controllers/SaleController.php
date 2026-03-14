<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|in:Efectivo,Transferencia,Tarjeta,Otro',
            'status' => 'nullable|string|in:completada,anulada',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $query = Sale::with(['user', 'saleProducts.product']);

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

        $sales = $query->latest('sale_date')
            ->latest()
            ->paginate(10)
            ->appends($request->all());

        $todayTotal = Sale::whereDate('sale_date', today())
            ->where('status', '!=', 'anulada')
            ->sum('total_amount');

        $todayCount = Sale::whereDate('sale_date', today())
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
            'customer_name'       => 'nullable|string|max:255',
            'payment_method'      => 'required|string|in:Efectivo,Transferencia,Tarjeta,Otro',
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

            DB::transaction(function () use ($validated) {

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
                    'sale_date'      => now(),
                    'payment_method' => $validated['payment_method'],
                    'user_id'        => auth()->id(),
                    'status'         => 'completada',
                ]);

                $totalSaleAmount = 0;

                foreach ($productData as $productId => $data) {

                    $quantity = $data['quantity'];
                    $index    = $data['index'];

                    $product = \App\Models\Product::where('id', $productId)
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

                    $itemTotal = $product->sale_price * $quantity;
                    $totalSaleAmount += $itemTotal;

                    $sale->saleProducts()->create([
                        'product_id'  => $product->id,
                        'quantity'    => $quantity,
                        'unit_price'  => $product->sale_price,
                        'total_price' => $itemTotal,
                    ]);

                    // ANTES:
                    // $product->decrementStock($quantity, 'sale', "Venta #{$sale->id}");

                    // DESPUÉS:
                    $inventoryService = app(\App\Services\InventoryService::class);
                    $inventoryService->deductStock($product->id, $quantity, "Venta #{$sale->id}");
                }

                $sale->update([
                    'total_amount' => $totalSaleAmount
                ]);

            });

        } catch (\Illuminate\Validation\ValidationException $e) {

            return $e->getResponse();

        }

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