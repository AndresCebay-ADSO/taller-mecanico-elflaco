<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    /**
     * Corrige proveedor, precios y (si el lote no se ha usado) la cantidad de un lote de compra.
     */
    public function update(Request $request, Batch $batch)
    {
        $this->ensureCurrentBranch(Product::withTrashed()->find($batch->product_id)?->branch_id);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'cost_price'  => 'required|numeric|min:0|max:99999999',
            'sale_price'  => 'required|numeric|min:0|max:99999999',
            'quantity'    => 'sometimes|required|integer|min:1|max:100000',
            'notes'       => 'required|string|min:10|max:1000',
        ]);

        try {
            DB::transaction(function () use ($validated, $batch) {
                $lockedBatch = Batch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
                $product = Product::withTrashed()->whereKey($lockedBatch->product_id)->lockForUpdate()->firstOrFail();

                // La cantidad solo se corrige si el lote no ha tenido consumos.
                $canChangeQuantity = isset($validated['quantity'])
                    && (int) $lockedBatch->remaining_stock === (int) $lockedBatch->quantity;

                if ($canChangeQuantity) {
                    $diff = (int) $validated['quantity'] - (int) $lockedBatch->quantity;

                    if ($product->stock + $diff < 0) {
                        throw new BusinessRuleException('La corrección dejaría el stock del producto en negativo.', 'quantity');
                    }

                    if ($diff !== 0) {
                        $product->increment('stock', $diff);
                        $lockedBatch->quantity = (int) $validated['quantity'];
                        $lockedBatch->remaining_stock = (int) $validated['quantity'];
                    }
                }

                $lockedBatch->supplier_id = $validated['supplier_id'];
                $lockedBatch->cost_price = $validated['cost_price'];
                $lockedBatch->sale_price = $validated['sale_price'];
                $lockedBatch->save();

                $movement = InventoryMovement::where('batch_id', $lockedBatch->id)
                    ->where('movement_type', 'purchase')
                    ->first();

                if ($movement) {
                    $movement->supplier_id = $validated['supplier_id'];
                    $movement->unit_price = $validated['cost_price'];
                    $movement->notes = $validated['notes'];

                    if ($canChangeQuantity) {
                        $movement->quantity = (int) $validated['quantity'];
                    }

                    $movement->save();
                }
            });
        } catch (BusinessRuleException $exception) {
            return redirect()->route('inventory.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('inventory.index')->with('success', 'Lote corregido correctamente.');
    }
}
