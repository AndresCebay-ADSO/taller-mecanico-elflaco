<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopJob extends Model
{
    protected $fillable = [
        'service_order_id',
        'job_type_id',
        'mechanic_id',
        'customer_name',
        'customer_phone',
        'vehicle_info',
        'description',
        'labor_cost',
        'mechanic_cost',
        'workshop_cost',
        'total_amount',
        'status',
        'started_at',
        'completed_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($job) {
            if ($job->job_type_id) {
                $calculation = $job->jobType->calculateEarnings($job->labor_cost);
                $job->mechanic_cost = $calculation['mechanic'];
                $job->workshop_cost = $calculation['workshop'];
                $job->total_amount = $calculation['total'];
            }
        });
    }

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function jobProducts()
    {
        return $this->hasMany(JobProduct::class, 'job_id');
    }
}
