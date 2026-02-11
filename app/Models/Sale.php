<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'customer_name',
        'total_amount',
        'sale_date',
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }
}
