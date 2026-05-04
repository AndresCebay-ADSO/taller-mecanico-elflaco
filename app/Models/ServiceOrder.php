<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ServiceOrder extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'vehicle_info',
        'service_description',
        'branch_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $serviceOrder) {
            if (!$serviceOrder->isDirty('status')) {
                return;
            }

            $originalStatus = $serviceOrder->getOriginal('status');

            if ($originalStatus === null || $originalStatus === $serviceOrder->status) {
                return;
            }

            $originalState = new self();
            $originalState->status = $originalStatus;

            if (!$originalState->canTransitionTo($serviceOrder->status)) {
                throw new InvalidArgumentException(
                    "Invalid transition from {$originalStatus} to {$serviceOrder->status}."
                );
            }
        });
    }

    public function workshopJobs()
    {
        return $this->hasMany(WorkshopJob::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if ($newStatus === $this->status) {
            return true;
        }

        $validTransitions = [
            self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? [], true);
    }

    public function canReceiveJobs(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch($query, ?int $branchId = null)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
