<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'category',
        'branch_id',
        'purchase_price',
        'sale_price',
        'stock',
        'min_stock',
        'upc',
    ];

    /**
     * Get the last supplier from the most recent batch.
     */
    public function lastSupplier()
    {
        return $this->batches()
            ->latest('created_at')
            ->with('supplier')
            ->first()
            ?->supplier;
    }

    /**
     * Multiple suppliers for this product (primary relationship)
     */
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class);
    }

    /**
     * @deprecated Use InventoryService::registerPurchaseBatch() instead.
     * Legacy method kept as guard — throws exception if called accidentally.
     */
    public function incrementStock($quantity, $unitPrice = null, $supplierId = null, $reference = null)
    {
        throw new \BadMethodCallException(
            'incrementStock() esta deprecado. Usar InventoryService::registerPurchaseBatch() para operaciones con trazabilidad FIFO.'
        );
    }

    /**
     * @deprecated Use InventoryService::reverseStockFromSale() instead.
     * Legacy method kept as guard — throws exception if called accidentally.
     */
    public function reverseStock($quantity, $reference = null)
    {
        throw new \BadMethodCallException(
            'reverseStock() esta deprecado. Usar InventoryService::reverseStockFromSale() para reversas con trazabilidad FIFO.'
        );
    }

    /**
     * @deprecated Use InventoryService::deductStock() instead.
     * Legacy method kept as guard — throws exception if called accidentally.
     */
    public function decrementStock($quantity, $reason = 'sale', $reference = null)
    {
        throw new \BadMethodCallException(
            'decrementStock() esta deprecado. Usar InventoryService::deductStock() para operaciones con trazabilidad FIFO.'
        );
    }

    public function jobProducts()
    {
        return $this->hasMany(JobProduct::class);
    }

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function productPurchases()
    {
        return $this->hasMany(ProductPurchase::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch($query, ?int $branchId = null)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
