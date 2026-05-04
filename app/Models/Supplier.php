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

    /**
     * Products associated through pivot table
     */
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
