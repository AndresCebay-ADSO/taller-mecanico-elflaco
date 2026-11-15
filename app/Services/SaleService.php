<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * Registra una venta de mostrador descontando stock FIFO.
     *
     * @param  array<int, array{id: int|string, quantity: int|string}>  $items
     * @return array{sale: Sale, low_stock: bool}
     *
     * @throws InsufficientStockException|BusinessRuleException
     */
    public function create(
        int $branchId,
        ?int $userId,
        ?string $customerName,
        string $paymentMethod,
        array $items
    ): array {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (int) $item['id'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];
        }

        // Bloquear siempre en el mismo orden evita deadlocks entre ventas simultaneas.
        ksort($quantities);

        return DB::transaction(function () use ($branchId, $userId, $customerName, $paymentMethod, $quantities) {
            $sale = Sale::create([
                'customer_name' => $customerName ?: 'Venta Mostrador',
                'total_amount' => 0,
                'branch_id' => $branchId,
                'sale_date' => now(),
                'payment_method' => $paymentMethod,
                'user_id' => $userId,
                'status' => 'completada',
            ]);

            $totalSaleAmount = 0;
            $hasLowStock = false;

            foreach ($quantities as $productId => $quantity) {
                $belongsToBranch = Product::whereKey($productId)->where('branch_id', $branchId)->exists();

                if (!$belongsToBranch) {
                    throw new BusinessRuleException('Uno de los productos no pertenece a la sucursal activa.', 'products');
                }

                $tramos = $this->inventory->deductStock($productId, $quantity, "Venta #{$sale->id}");

                // Una linea por cada lote consumido: conserva el precio real de cada tramo.
                foreach ($tramos as $tramo) {
                    $lineTotal = $tramo['unit_price'] * $tramo['quantity'];
                    $totalSaleAmount += $lineTotal;

                    $sale->saleProducts()->create([
                        'product_id' => $productId,
                        'quantity' => $tramo['quantity'],
                        'unit_price' => $tramo['unit_price'],
                        'total_price' => $lineTotal,
                    ]);
                }

                $product = Product::find($productId);
                if ($product && $product->stock <= $product->min_stock) {
                    $hasLowStock = true;
                }
            }

            $sale->update(['total_amount' => $totalSaleAmount]);

            return ['sale' => $sale, 'low_stock' => $hasLowStock];
        }, 3);
    }

    /**
     * Anula una venta y devuelve el stock a sus lotes. Devuelve false si ya estaba anulada.
     */
    public function cancel(Sale $sale): bool
    {
        return DB::transaction(function () use ($sale) {
            $lockedSale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($lockedSale->isCancelled()) {
                return false;
            }

            $lockedSale->update(['status' => Sale::STATUS_CANCELLED]);
            $this->inventory->reverseStockFromSale($lockedSale->id);

            return true;
        }, 3);
    }
}
