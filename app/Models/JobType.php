<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'calculation_type',
        'mechanic_percentage',
        'workshop_percentage',
        'percentage_fixed_total',
        'fixed_mechanic_amount',
        'fixed_workshop_amount',
        'allow_products',
        'allow_custom_labor',
        'is_active',
    ];

    protected $casts = [
        'allow_products' => 'boolean',
        'allow_custom_labor' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function workshopJobs()
    {
        return $this->hasMany(WorkshopJob::class);
    }
}
