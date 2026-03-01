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

    /**
     * Increment stock and record movement
     */
    public function incrementStock($quantity, $unitPrice = null, $supplierId = null, $reference = null)
    {
        $this->increment('stock', $quantity);
        
        if ($unitPrice) {
            $this->update(['purchase_price' => $unitPrice]);
        }

        return $this->inventoryMovements()->create([
            'movement_type' => 'purchase',
            'quantity' => $quantity,
            'unit_price' => $unitPrice ?? $this->purchase_price,
            'supplier_id' => $supplierId ?? $this->supplier_id,
            'reference' => $reference,
            'movement_date' => now(),
        ]);
    }

    /**
     * Reverse stock from a cancelled sale and record the movement as 'reversal'
     */
    public function reverseStock($quantity, $reference = null)
    {
        $this->increment('stock', $quantity);

        return $this->inventoryMovements()->create([
            'movement_type' => 'reversal',
            'quantity'      => $quantity,
            'unit_price'    => $this->purchase_price,
            'reference'     => $reference,
            'movement_date' => now(),
        ]);
    }

    /**
     * Decrement stock and record movement
     */
    public function decrementStock($quantity, $reason = 'sale', $reference = null)
    {
        $this->decrement('stock', $quantity);

        return $this->inventoryMovements()->create([
            'movement_type' => $reason,
            'quantity' => -$quantity,
            'unit_price' => $this->sale_price,
            'reference' => $reference,
            'movement_date' => now(),
        ]);
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
