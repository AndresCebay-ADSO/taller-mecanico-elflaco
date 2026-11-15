<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Punto unico de entrada para cualquier cambio de stock.
 *
 * Invariante: products.stock === SUM(batches.remaining_stock) para cada producto.
 * Todos los metodos publicos mantienen ambos lados sincronizados para que el costeo FIFO sea confiable.
 */
class InventoryService
{
    /**
     * Movimientos que se valoran al costo del lote en lugar de al precio de venta.
     */
    private const COST_VALUED_MOVEMENTS = ['adjustment', 'transfer_out'];

    /**
     * Lote nuevo de un producto. Se vende al precio del producto (un solo precio por producto, decisión 4)
     * salvo que se indique otro; cada lote guarda su propio costo. No toca el stock ni crea el movimiento.
     */
    public function newBatch(
        Product $product,
        int $quantity,
        float|string|null $costPrice,
        ?int $supplierId = null,
        float|string|null $salePrice = null,
        mixed $purchasedAt = null,
    ): Batch {
        return Batch::create([
            'product_id' => $product->id,
            'supplier_id' => $supplierId,
            'cost_price' => $costPrice,
            'sale_price' => $salePrice ?? $product->sale_price,
            'quantity' => $quantity,
            'remaining_stock' => $quantity,
            'purchased_at' => $purchasedAt ?? now(),
        ]);
    }

    /**
     * Productos con stock de la sucursal y sus tramos de precio FIFO, para calcular totales en pantalla
     * igual que se cobrarán. Solo expone datos de venta (nunca costos).
     *
     * @return Collection<int, array{id: int, name: string, stock: int, price: float, tiers: array<int, array{qty: int, price: float}>}>
     */
    public function sellableProducts(?int $branchId): Collection
    {
        return Product::forBranch($branchId)
            ->where('stock', '>', 0)
            ->with(['batches' => fn ($query) => $query->withStock()->fifo()->orderBy('id')])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                // Lotes seguidos con el mismo precio forman un solo tramo.
                $tiers = [];
                foreach ($product->batches as $batch) {
                    $price = (float) ($batch->sale_price ?? $product->sale_price);
                    $last = array_key_last($tiers);

                    if ($last !== null && $tiers[$last]['price'] === $price) {
                        $tiers[$last]['qty'] += (int) $batch->remaining_stock;
                    } else {
                        $tiers[] = ['qty' => (int) $batch->remaining_stock, 'price' => $price];
                    }
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => (int) $product->stock,
                    'price' => $tiers[0]['price'] ?? (float) $product->sale_price,
                    'tiers' => $tiers ?: [['qty' => (int) $product->stock, 'price' => (float) $product->sale_price]],
                ];
            })
            ->values();
    }

    /**
     * Deduct stock using FIFO logic.
     *
     * @return array<int, array{quantity: int, unit_price: float, cost_price: float, batch_id: int, supplier_id: int|null}>
     */
    public function deductStock(
        int $productId,
        int $quantity,
        ?string $reference = null,
        string $movementType = 'sale',
        ?string $notes = null
    ): array {
        $this->ensureTransaction('deductStock');

        if ($quantity <= 0) {
            throw new RuntimeException('La cantidad a descontar debe ser mayor que cero.');
        }

        $product = $this->lockProduct($productId);

        if ($product->stock < $quantity) {
            throw new InsufficientStockException(
                "Stock insuficiente para {$product->name}. Disponible: {$product->stock}. Requerido: {$quantity}."
            );
        }

        return $this->consumeBatches($product, $quantity, $movementType, $reference, $notes);
    }

    /**
     * Restore stock after canceling a sale.
     * Must be called within an active DB transaction.
     */
    public function reverseStockFromSale(int $saleId): void
    {
        $this->reverseMovements(["Venta #{$saleId}"], ['sale'], "Anulacion Venta #{$saleId}");
    }

    /**
     * Devuelve al stock cada movimiento de salida con esas referencias, al mismo lote del que salio.
     *
     * @param  array<int, string>  $references
     * @param  array<int, string>  $movementTypes
     * @return int Cantidad de movimientos revertidos.
     */
    public function reverseMovements(array $references, array $movementTypes, string $reversalReference): int
    {
        $this->ensureTransaction('reverseMovements');

        $movements = InventoryMovement::whereIn('reference', $references)
            ->whereIn('movement_type', $movementTypes)
            ->where('quantity', '<', 0)
            ->orderByDesc('id')
            ->get();

        foreach ($movements as $movement) {
            $this->returnToStock(
                $movement->product_id,
                abs($movement->quantity),
                $reversalReference,
                $movement->batch_id,
                $movement->unit_price,
                $movement->supplier_id
            );
        }

        return $movements->count();
    }

    /**
     * Reingresa unidades al stock. Usa el lote original si se conoce; si no, abre un lote nuevo al costo del producto.
     * Must be called within an active DB transaction.
     */
    public function returnToStock(
        int $productId,
        int $quantity,
        string $reference,
        ?int $batchId = null,
        float|string|null $unitPrice = null,
        ?int $supplierId = null
    ): void {
        $this->ensureTransaction('returnToStock');

        $product = Product::withTrashed()
            ->whereKey($productId)
            ->lockForUpdate()
            ->first();

        if (!$product) {
            $this->safeLog('warning', 'Producto no encontrado al devolver stock.', [
                'product_id' => $productId,
                'reference' => $reference,
            ]);
            return;
        }

        if ($product->trashed()) {
            $product->restore();

            $this->safeLog('info', 'Producto restaurado automaticamente al devolver stock.', [
                'product_id' => $product->id,
                'reference' => $reference,
            ]);
        }

        $batch = $batchId
            ? Batch::whereKey($batchId)->lockForUpdate()->first()
            : null;

        if ($batch) {
            $batch->increment('remaining_stock', $quantity);
        } else {
            $batch = $this->newBatch($product, $quantity, $product->purchase_price, $supplierId);
        }

        $product->increment('stock', $quantity);

        InventoryMovement::create([
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'movement_type' => 'reversal',
            'quantity' => $quantity,
            'unit_price' => $unitPrice ?? $product->sale_price,
            'supplier_id' => $supplierId ?? $batch->supplier_id,
            'reference' => $reference,
            'movement_date' => now(),
        ]);
    }

    /**
     * Register a new batch from a purchase.
     */
    public function registerPurchaseBatch(array $data): Batch
    {
        return DB::transaction(function () use ($data) {
            $product = $this->lockProduct((int) $data['product_id']);
            $purchasedAt = $data['purchased_at'] ?? now();

            $batch = $this->newBatch(
                $product,
                (int) $data['quantity'],
                $data['cost_price'],
                $data['supplier_id'] ?? null,
                $data['sale_price'] ?? null,
                $purchasedAt,
            );

            InventoryMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'movement_type' => 'purchase',
                'quantity' => $data['quantity'],
                'unit_price' => $data['cost_price'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference' => $data['reference'] ?? 'Compra Lote #' . $batch->id,
                'notes' => $data['notes'] ?? null,
                'movement_date' => $purchasedAt,
            ]);

            $product->increment('stock', $data['quantity']);
            $product->update([
                'purchase_price' => $data['cost_price'],
                'sale_price' => $batch->sale_price,
            ]);

            // Un solo precio por producto (decisión 4): las unidades que ya había pasan al precio de esta compra.
            // Cada lote conserva su costo, así la ganancia sigue saliendo con lo que costó cada unidad.
            $product->batches()->withStock()->update(['sale_price' => $batch->sale_price]);

            return $batch;
        });
    }

    /**
     * Register a stock adjustment with traceability.
     * Positive adjustments open a new batch at the product cost; negative ones consume batches FIFO.
     */
    public function adjustStock(int $productId, int $quantity, string $reason, ?string $notes = null): void
    {
        if ($quantity === 0) {
            throw new RuntimeException('La cantidad del ajuste no puede ser cero.');
        }

        DB::transaction(function () use ($productId, $quantity, $reason, $notes) {
            $product = $this->lockProduct($productId);
            $reference = strtoupper($reason);

            if ($quantity > 0) {
                $batch = $this->newBatch($product, $quantity, $product->purchase_price);

                $product->increment('stock', $quantity);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'adjustment',
                    'quantity' => $quantity,
                    'unit_price' => $product->purchase_price,
                    'reference' => $reference,
                    'notes' => $notes,
                    'movement_date' => now(),
                ]);

                return;
            }

            $unitsToRemove = abs($quantity);

            if ($product->stock < $unitsToRemove) {
                throw new InsufficientStockException(
                    "Stock insuficiente para {$product->name}. Disponible: {$product->stock}. Requerido: {$unitsToRemove}."
                );
            }

            $this->consumeBatches($product, $unitsToRemove, 'adjustment', $reference, $notes);
        }, 3);
    }

    /**
     * Productos cuyo stock no coincide con la suma de sus lotes.
     *
     * @return Collection<int, Product>
     */
    public function findBatchDiscrepancies(): Collection
    {
        return Product::withTrashed()
            ->select('products.id', 'products.name', 'products.branch_id', 'products.stock')
            ->selectSub(
                Batch::selectRaw('COALESCE(SUM(remaining_stock), 0)')->whereColumn('batches.product_id', 'products.id'),
                'batch_stock'
            )
            ->orderBy('products.id')
            ->get()
            ->filter(fn (Product $product) => (int) $product->stock !== (int) $product->batch_stock)
            ->values();
    }

    /**
     * Alinea los lotes de un producto con su stock registrado (datos creados antes de exigir lotes).
     *
     * @return int Unidades agregadas (+) o retiradas (-) de los lotes.
     */
    public function reconcileBatches(int $productId): int
    {
        return DB::transaction(function () use ($productId) {
            $product = Product::withTrashed()->whereKey($productId)->lockForUpdate()->firstOrFail();

            $batches = Batch::where('product_id', $product->id)
                ->withStock()
                ->fifo()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $difference = (int) $product->stock - (int) $batches->sum('remaining_stock');

            if ($difference > 0) {
                $this->newBatch($product, $difference, $product->purchase_price);
            }

            $excess = -$difference;

            foreach ($batches as $batch) {
                if ($excess <= 0) {
                    break;
                }

                $take = min($batch->remaining_stock, $excess);
                $batch->decrement('remaining_stock', $take);
                $excess -= $take;
            }

            return $difference;
        });
    }

    /**
     * @return array<int, array{quantity: int, unit_price: float, cost_price: float, batch_id: int, supplier_id: int|null}>
     */
    private function consumeBatches(
        Product $product,
        int $quantity,
        string $movementType,
        ?string $reference,
        ?string $notes
    ): array {
        $batches = Batch::where('product_id', $product->id)
            ->withStock()
            ->fifo()
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ((int) $batches->sum('remaining_stock') < $quantity) {
            throw new InsufficientStockException(
                "El stock de {$product->name} no coincide con sus lotes. Un administrador debe ejecutar la conciliacion de inventario (php artisan inventory:check --fix)."
            );
        }

        $valueAtCost = in_array($movementType, self::COST_VALUED_MOVEMENTS, true);
        $remainingToDeduct = $quantity;
        $tramos = [];

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $deductFromThisBatch = min($batch->remaining_stock, $remainingToDeduct);
            $salePrice = (float) ($batch->sale_price ?? $product->sale_price);
            $costPrice = (float) $batch->cost_price;

            $batch->decrement('remaining_stock', $deductFromThisBatch);

            InventoryMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'movement_type' => $movementType,
                'quantity' => -$deductFromThisBatch,
                'unit_price' => $valueAtCost ? $costPrice : $salePrice,
                'supplier_id' => $batch->supplier_id,
                'reference' => $reference,
                'notes' => $notes,
                'movement_date' => now(),
            ]);

            $tramos[] = [
                'quantity' => $deductFromThisBatch,
                'unit_price' => $salePrice,
                'cost_price' => $costPrice,
                'batch_id' => $batch->id,
                'supplier_id' => $batch->supplier_id,
            ];

            $remainingToDeduct -= $deductFromThisBatch;
        }

        $product->decrement('stock', $quantity);

        return $tramos;
    }

    private function lockProduct(int $productId): Product
    {
        return Product::whereKey($productId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureTransaction(string $method): void
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException("{$method} debe ejecutarse dentro de una transaccion activa.");
        }
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::{$level}($message, $context);
        } catch (Throwable) {
        }
    }
}
