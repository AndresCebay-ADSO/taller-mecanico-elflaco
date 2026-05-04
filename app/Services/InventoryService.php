<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use RuntimeException;

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
     *
     * @return array<int, array{quantity: int, unit_price: float}>
     */
    public function deductStock(
        int $productId,
        int $quantity,
        ?string $notes = null,
        string $movementType = 'sale'
    ): array {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('deductStock debe ejecutarse dentro de una transaccion activa.');
        }

        $product = Product::whereKey($productId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($product->stock < $quantity) {
            throw new InsufficientStockException(
                "Stock insuficiente para {$product->name}. Disponible: {$product->stock}. Requerido: {$quantity}."
            );
        }

        $remainingToDeduct = $quantity;
        $tramos = [];

        $batches = Batch::where('product_id', $productId)
            ->withStock()
            ->fifo()
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $deductFromThisBatch = min($batch->remaining_stock, $remainingToDeduct);
            $batchUnitPrice = (float) ($batch->sale_price ?? $product->sale_price);

            $batch->decrement('remaining_stock', $deductFromThisBatch);

            InventoryMovement::create([
                'product_id' => $productId,
                'batch_id' => $batch->id,
                'movement_type' => $movementType,
                'quantity' => -$deductFromThisBatch,
                'unit_price' => $batchUnitPrice,
                'supplier_id' => $batch->supplier_id,
                'reference' => $notes,
                'movement_date' => now(),
            ]);

            $tramos[] = [
                'quantity' => $deductFromThisBatch,
                'unit_price' => $batchUnitPrice,
            ];

            $remainingToDeduct -= $deductFromThisBatch;
        }

        if ($remainingToDeduct > 0) {
            throw new InsufficientStockException(
                "Error critico: El stock de los lotes no coincide con el total del producto {$product->name}."
            );
        }

        $product->decrement('stock', $quantity);

        return $tramos;
    }

    /**
     * Restore stock after canceling a sale.
     * Must be called within an active DB transaction (SaleController::cancel does this).
     */
    public function reverseStockFromSale(int $saleId): void
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('reverseStockFromSale debe ejecutarse dentro de una transaccion activa.');
        }

        $movements = InventoryMovement::where('reference', "Venta #{$saleId}")
            ->where('movement_type', 'sale')
            ->orderBy('id', 'desc')
            ->get();

        foreach ($movements as $movement) {
            $product = Product::withTrashed()
                ->whereKey($movement->product_id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                $this->safeLog('warning', 'Producto no encontrado al revertir venta.', [
                    'sale_id' => $saleId,
                    'product_id' => $movement->product_id,
                    'movement_id' => $movement->id,
                ]);
                continue;
            }

            if ($product->trashed()) {
                $product->restore();

                $this->safeLog('info', 'Producto restaurado automaticamente durante reversa de venta.', [
                    'sale_id' => $saleId,
                    'product_id' => $product->id,
                    'movement_id' => $movement->id,
                ]);
            }

            $quantityToRestore = abs($movement->quantity);

            if ($movement->batch_id) {
                $batch = Batch::whereKey($movement->batch_id)
                    ->lockForUpdate()
                    ->first();

                if ($batch) {
                    $batch->increment('remaining_stock', $quantityToRestore);
                }
            }

            $product->increment('stock', $quantityToRestore);

            InventoryMovement::create([
                'product_id' => $movement->product_id,
                'batch_id' => $movement->batch_id,
                'movement_type' => 'reversal',
                'quantity' => $quantityToRestore,
                'unit_price' => $movement->unit_price,
                'supplier_id' => $movement->supplier_id,
                'reference' => "Anulacion Venta #{$saleId}",
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
                'product_id' => $data['product_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'] ?? $data['sale_price'] ?? 0,
                'sale_price' => $data['sale_price'] ?? $data['selling_price'] ?? null,
                'quantity' => $data['quantity'],
                'remaining_stock' => $data['quantity'],
                'purchased_at' => $data['purchased_at'] ?? now(),
            ]);

            InventoryMovement::create([
                'product_id' => $data['product_id'],
                'batch_id' => $batch->id,
                'movement_type' => 'purchase',
                'quantity' => $data['quantity'],
                'unit_price' => $data['cost_price'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference' => $data['reference'] ?? 'Compra Lote #' . $batch->id,
                'movement_date' => $data['purchased_at'] ?? now(),
            ]);

            $product = Product::findOrFail($data['product_id']);
            $product->increment('stock', $data['quantity']);
            $product->update([
                'purchase_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'] ?? $data['selling_price'] ?? $product->sale_price,
            ]);

            return $batch;
        });
    }

    /**
     * Register a stock adjustment with traceability.
     */
    public function adjustStock(int $productId, int $quantity, string $reason, ?string $notes = null): void
    {
        DB::transaction(function () use ($productId, $quantity, $reason, $notes) {
            $product = Product::whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity === 0) {
                throw new RuntimeException('La cantidad del ajuste no puede ser cero.');
            }

            if ($quantity < 0 && $product->stock < abs($quantity)) {
                throw new InsufficientStockException(
                    "Stock insuficiente para {$product->name}. Disponible: {$product->stock}. Requerido: " . abs($quantity) . '.'
                );
            }

            $product->increment('stock', $quantity);

            InventoryMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'adjustment',
                'quantity' => $quantity,
                'unit_price' => $product->purchase_price,
                'reference' => strtoupper($reason),
                'notes' => $notes,
                'movement_date' => now(),
            ]);
        });
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::{$level}($message, $context);
        } catch (Throwable) {
        }
    }
}
