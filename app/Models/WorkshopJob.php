<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class WorkshopJob extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

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

        static::saving(function (self $job) {
            if ($job->job_type_id) {
                $calculation = $job->jobType->calculateEarnings($job->labor_cost);
                $job->mechanic_cost = $calculation['mechanic'];
                $job->workshop_cost = $calculation['workshop'];
                $job->total_amount = $calculation['total'];
            }
        });

        static::updating(function (self $job) {
            if (!$job->isDirty('status')) {
                return;
            }

            $originalStatus = $job->getOriginal('status');

            if ($originalStatus === null || $originalStatus === $job->status) {
                return;
            }

            $originalState = new self();
            $originalState->status = $originalStatus;

            if (!$originalState->canTransitionTo($job->status)) {
                throw new InvalidArgumentException(
                    "Invalid transition from {$originalStatus} to {$job->status}."
                );
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

    public function canTransitionTo(string $newStatus): bool
    {
        if ($newStatus === $this->status) {
            return true;
        }

        $validTransitions = [
            self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? [], true);
    }
}
