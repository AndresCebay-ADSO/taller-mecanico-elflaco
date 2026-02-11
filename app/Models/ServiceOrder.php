<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'vehicle_info',
        'service_description',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workshopJobs()
    {
        return $this->hasMany(WorkshopJob::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
