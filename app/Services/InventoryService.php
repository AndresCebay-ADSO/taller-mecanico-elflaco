<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Get the active FIFO batch (oldest with stock).
     */
    public function getActiveBatch(int $productId): ?Batch
    {
        return Batch::where('product_id', $productId)
            ->withStock()
            ->fifo()
            ->first();
    }

    /**
     * Get the selling price from the active FIFO batch.
     * Fallback to product's selling price if no active batch.
     */
    public function getSellingPrice(int $productId): float
    {
        $activeBatch = $this->getActiveBatch($productId);
        
        if ($activeBatch) {
            return (float) $activeBatch->selling_price;
        }

        $product = Product::find($productId);
        return $product ? (float) $product->sale_price : 0.0;
    }

    /**
     * Deduct stock using FIFO logic.
     * IMPORTANT: This should be called WITHIN an existing DB transaction.
     */
    public function deductStock(int $productId, int $quantity, ?string $notes = null): void
    {
        $product = Product::findOrFail($productId);
        
        if ($product->stock < $quantity) {
            throw new InsufficientStockException("Stock insuficiente para {$product->name}. Disponible: {$product->stock}. Requerido: {$quantity}.");
        }

        $remainingToDeduct = $quantity;

        // Get all batches with stock ordered by FIFO
        $batches = Batch::where('product_id', $productId)
            ->withStock()
            ->fifo()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $deductFromThisBatch = min($batch->remaining_stock, $remainingToDeduct);

            // Update batch stock
            $batch->decrement('remaining_stock', $deductFromThisBatch);

            // Record movement for this batch
            InventoryMovement::create([
                'product_id'    => $productId,
                'batch_id'      => $batch->id,
                'movement_type' => 'sale',
                'quantity'      => -$deductFromThisBatch,
                'unit_price'    => $batch->selling_price,
                'supplier_id'   => $batch->supplier_id,
                'reference'     => $notes,
                'movement_date' => now(),
            ]);

            $remainingToDeduct -= $deductFromThisBatch;
        }

        // Final check if somehow we couldn't deduct everything from batches
        if ($remainingToDeduct > 0) {
            throw new InsufficientStockException("Error crítico: El stock de los lotes no coincide con el total del producto {$product->name}.");
        }

        // Update product total stock
        $product->decrement('stock', $quantity);
    }

    /**
     * Restaura stock al anular una venta.
     * Busca los inventory_movements con movement_type='sale' vinculados a la venta
     * y restaura remaining_stock en cada batch afectado en orden inverso (LIFO).
     */
    public function reverseStockFromSale(int $saleId): void
    {
        // Buscamos los movimientos de la venta por su referencia formatada en SaleController
        $movements = InventoryMovement::where('reference', "Venta #{$saleId}")
            ->where('movement_type', 'sale')
            ->orderBy('id', 'desc') // Orden inverso (LIFO) para la devolución
            ->get();

        foreach ($movements as $movement) {
            $product = Product::find($movement->product_id);
            if (!$product) continue;

            $quantityToRestore = abs($movement->quantity);

            // 1. Restaurar stock en el lote si existe
            if ($movement->batch_id) {
                $batch = Batch::find($movement->batch_id);
                if ($batch) {
                    $batch->increment('remaining_stock', $quantityToRestore);
                }
            }

            // 2. Restaurar stock general del producto
            $product->increment('stock', $quantityToRestore);

            // 3. Registrar el movimiento de reversión
            InventoryMovement::create([
                'product_id'    => $movement->product_id,
                'batch_id'      => $movement->batch_id,
                'movement_type' => 'reversal',
                'quantity'      => $quantityToRestore,
                'unit_price'    => $movement->unit_price,
                'supplier_id'   => $movement->supplier_id,
                'reference'     => "Anulación Venta #{$saleId}",
                'movement_date' => now(),
            ]);
        }
    }

    /**
     * Register a new batch from a purchase.
     */
    public function registerPurchaseBatch(array $data): Batch
    {
        return DB::transaction(function () use ($data) {
            $batch = Batch::create([
                'product_id'      => $data['product_id'],
                'supplier_id'     => $data['supplier_id'] ?? null,
                'cost_price'      => $data['cost_price'],
                'selling_price'   => $data['selling_price'],
                'quantity'        => $data['quantity'],
                'remaining_stock' => $data['quantity'],
                'purchased_at'    => $data['purchased_at'] ?? now(),
            ]);

            // Record movement for the new batch
            InventoryMovement::create([
                'product_id'    => $data['product_id'],
                'batch_id'      => $batch->id,
                'movement_type' => 'purchase',
                'quantity'      => $data['quantity'],
                'unit_price'    => $data['cost_price'],
                'supplier_id'   => $data['supplier_id'] ?? null,
                'reference'     => $data['reference'] ?? 'Compra Lote #' . $batch->id,
                'movement_date' => $data['purchased_at'] ?? now(),
            ]);

            // Update product stock and pricing
            $product = Product::findOrFail($data['product_id']);
            $product->increment('stock', $data['quantity']);
            $product->update([
                'purchase_price' => $data['cost_price'],
                'sale_price'     => $data['selling_price']
            ]);

            return $batch;
        });
    }
}
