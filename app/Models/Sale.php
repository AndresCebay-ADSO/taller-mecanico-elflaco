<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'customer_name',
        'total_amount',
        'branch_id',
        'sale_date',
        'payment_method',
        'user_id',
        'status',
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function getTotalItemsAttribute()
    {
        return $this->saleProducts->sum('quantity');
    }
}
