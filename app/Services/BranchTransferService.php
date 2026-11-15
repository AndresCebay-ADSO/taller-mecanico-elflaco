<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\BranchTransfer;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BranchTransferService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * Mueve el stock consumiendo lotes FIFO en origen y creando lotes con el mismo costo en destino.
     *
     * @throws InsufficientStockException|BusinessRuleException
     */
    public function complete(BranchTransfer $transfer, ?int $completedBy): BranchTransfer
    {
        return DB::transaction(function () use ($transfer, $completedBy) {
            $lockedTransfer = BranchTransfer::with(['fromBranch', 'toBranch'])
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransfer->status !== BranchTransfer::STATUS_IN_TRANSIT) {
                throw new BusinessRuleException('La transferencia ya fue procesada o no está en tránsito.', 'status');
            }

            $sourceProduct = Product::where('id', $lockedTransfer->product_id)
                ->where('branch_id', $lockedTransfer->from_branch_id)
                ->first();

            if (!$sourceProduct) {
                throw new BusinessRuleException('El producto ya no pertenece a la sucursal de origen.', 'status');
            }

            $reference = "Transferencia #{$lockedTransfer->id}";

            $tramos = $this->inventory->deductStock(
                $sourceProduct->id,
                $lockedTransfer->quantity,
                $reference,
                'transfer_out',
                "Transferencia a sucursal {$lockedTransfer->toBranch->name}"
            );

            $destinationProduct = $this->findOrCreateDestinationProduct($lockedTransfer, $sourceProduct);

            foreach ($tramos as $tramo) {
                // Llega con su costo y se vende al precio del producto en el destino (decisión 4).
                $batch = $this->inventory->newBatch($destinationProduct, $tramo['quantity'], $tramo['cost_price'], $tramo['supplier_id']);

                InventoryMovement::create([
                    'product_id' => $destinationProduct->id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'transfer_in',
                    'quantity' => $tramo['quantity'],
                    'unit_price' => $tramo['cost_price'],
                    'supplier_id' => $tramo['supplier_id'],
                    'reference' => $reference,
                    'notes' => "Transferencia desde sucursal {$lockedTransfer->fromBranch->name}",
                    'movement_date' => now(),
                ]);
            }

            $destinationProduct->increment('stock', $lockedTransfer->quantity);

            $lockedTransfer->update([
                'status' => BranchTransfer::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $completedBy,
            ]);

            return $lockedTransfer;
        }, 3);
    }

    /**
     * Busca el mismo producto en la sucursal destino (por codigo o por nombre y categoria) o lo crea sin stock.
     */
    private function findOrCreateDestinationProduct(BranchTransfer $transfer, Product $sourceProduct): Product
    {
        $destinationProduct = Product::where('branch_id', $transfer->to_branch_id)
            ->where(function ($query) use ($sourceProduct) {
                if (!empty($sourceProduct->upc)) {
                    $query->where('upc', $sourceProduct->upc);
                } else {
                    $query->where('name', $sourceProduct->name)
                          ->where('category', $sourceProduct->category);
                }
            })
            ->lockForUpdate()
            ->first();

        if ($destinationProduct) {
            return $destinationProduct;
        }

        $destinationProduct = Product::create([
            'name' => $sourceProduct->name,
            'category' => $sourceProduct->category,
            'branch_id' => $transfer->to_branch_id,
            'purchase_price' => $sourceProduct->purchase_price,
            'sale_price' => $sourceProduct->sale_price,
            'stock' => 0,
            'min_stock' => $sourceProduct->min_stock,
            'upc' => $sourceProduct->upc,
        ]);

        $destinationProduct->suppliers()->sync($sourceProduct->suppliers()->pluck('suppliers.id'));

        return $destinationProduct;
    }
}
