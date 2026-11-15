<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, BelongsToBranch;

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

    /** Productos en su stock mínimo o por debajo. */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /** Estado del stock: «Agotado», «Stock bajo» o «Normal» (DESIGN.md §3). */
    public function stockLabel(): string
    {
        return match (true) {
            $this->stock <= 0 => 'Agotado',
            $this->isLowStock() => 'Stock bajo',
            default => 'Normal',
        };
    }

    public function stockTone(): string
    {
        return match (true) {
            $this->trashed() => 'neutral',
            $this->stock <= 0 => 'danger',
            $this->isLowStock() => 'warning',
            default => 'neutral',
        };
    }

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

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
