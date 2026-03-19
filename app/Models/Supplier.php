<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'active',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productsManyToMany()
    {
        return $this->belongsToMany(Product::class);
    }

    public function productPurchases()
    {
        return $this->hasMany(ProductPurchase::class);
    }
}
