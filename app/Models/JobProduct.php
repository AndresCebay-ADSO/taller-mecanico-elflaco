<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobProduct extends Model
{
    protected $fillable = [
        'job_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
