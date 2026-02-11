<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'default_description',
        'calculation_type',
        'mechanic_percentage',
        'workshop_percentage',
        'percentage_fixed_total',
        'fixed_mechanic_amount',
        'fixed_workshop_amount',
        'allow_products',
        'allow_custom_labor',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'allow_products' => 'boolean',
        'allow_custom_labor' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'mechanic_percentage' => 'decimal:2',
        'workshop_percentage' => 'decimal:2',
        'fixed_mechanic_amount' => 'decimal:2',
        'fixed_workshop_amount' => 'decimal:2',
    ];

    /**
     * Calculate earnings split based on rules
     */
    public function calculateEarnings($laborCost)
    {
        if ($this->calculation_type === 'percentage') {
            return [
                'mechanic' => $laborCost * ($this->mechanic_percentage / 100),
                'workshop' => $laborCost * ($this->workshop_percentage / 100),
                'total' => $laborCost
            ];
        }

        // Fixed amount logic
        return [
            'mechanic' => $this->fixed_mechanic_amount ?? 0,
            'workshop' => $this->fixed_workshop_amount ?? 0,
            'total' => ($this->fixed_mechanic_amount ?? 0) + ($this->fixed_workshop_amount ?? 0)
        ];
    }

    public function workshopJobs()
    {
        return $this->hasMany(WorkshopJob::class);
    }
}
