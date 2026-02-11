<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'supplier_id',
        'purchase_price',
        'sale_price',
        'stock',
        'min_stock',
        'upc',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
}
