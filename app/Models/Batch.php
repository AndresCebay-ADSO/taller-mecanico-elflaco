<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Batch extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'cost_price',
        'sale_price',
        'quantity',
        'remaining_stock',
        'purchased_at',
    ];

    protected $casts = [
        'cost_price'    => 'decimal:2',
        'sale_price'    => 'decimal:2',
        'purchased_at'  => 'datetime',
    ];

    /**
     * Relationship with Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship with Supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope to filter batches with stock
     */
    public function scopeWithStock(Builder $query): Builder
    {
        return $query->where('remaining_stock', '>', 0);
    }

    /**
     * Scope to order by FIFO (oldest first)
     */
    public function scopeFifo(Builder $query): Builder
    {
        return $query->orderBy('purchased_at', 'asc');
    }

    /**
     * Accessor to check if batch is active (has stock)
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->remaining_stock > 0;
    }
}
