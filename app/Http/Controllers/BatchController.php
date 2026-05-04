<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    /**
     * Update the specified batch in storage.
     */
    public function update(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'cost_price'  => 'required|numeric|min:0',
            'sale_price'  => 'required|numeric|min:0',
            'quantity'    => 'sometimes|required|integer|min:1',
            'notes'       => 'required|string|min:10',
        ]);

        try {
            DB::transaction(function () use ($validated, $batch) {
                // Store original state for M07 fix
                $wasUntouched = $batch->remaining_stock == $batch->quantity;

                // Determine if quantity can be modified
                if (isset($validated['quantity']) && $wasUntouched) {
                    $diff = $validated['quantity'] - $batch->quantity;

                    if ($diff != 0) {
                        // Recalculate global product stock
                        $product = $batch->product;
                        $product->increment('stock', $diff);

                        $batch->quantity = $validated['quantity'];
                        $batch->remaining_stock = $validated['quantity'];
                    }
                }

                // Update Batch Pricing and Supplier
                $batch->supplier_id = $validated['supplier_id'];
                $batch->cost_price = $validated['cost_price'];
                $batch->sale_price = $validated['sale_price'];
                $batch->save();

                // Update original purchase movement
                $movement = InventoryMovement::where('batch_id', $batch->id)
                    ->where('movement_type', 'purchase')
                    ->first();

                if ($movement) {
                    $movement->supplier_id = $validated['supplier_id'];
                    $movement->unit_price = $validated['cost_price'];
                    $movement->notes = $validated['notes'];
                    
                    if (isset($validated['quantity']) && $wasUntouched) {
                        $movement->quantity = $validated['quantity'];
                    }
                    
                    $movement->save();
                }
            });

            return redirect()->route('inventory.index')->with('success', 'Lote corregido correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('inventory.index')->with('error', 'Error al corregir el lote: ' . $e->getMessage());
        }
    }
}
