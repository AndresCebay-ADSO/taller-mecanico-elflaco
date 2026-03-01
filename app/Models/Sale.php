<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'customer_name',
        'total_amount',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalItemsAttribute()
    {
        return $this->saleProducts->sum('quantity');
    }
}
