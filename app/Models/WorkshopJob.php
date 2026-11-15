<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
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

    /** Trabajos que cuentan: los no cancelados. */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /** Trabajos completados desde `$from` (y hasta `$to`, si se da): son los que generan comisión. */
    public function scopeCompletedBetween(Builder $query, DateTimeInterface $from, ?DateTimeInterface $to = null): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->where('completed_at', '>=', $from)
            ->when($to, fn (Builder $query) => $query->where('completed_at', '<=', $to));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $job) {
            if ($job->job_type_id) {
                $calculation = $job->jobType->calculateEarnings($job->labor_cost);
                $job->mechanic_cost = $calculation['mechanic'];
                $job->workshop_cost = $calculation['workshop'];
                $job->total_amount = $calculation['total'];

                // En tipos de monto fijo la mano de obra cobrada es la suma fija: evita totales distintos entre vistas.
                if ($job->jobType->calculation_type === 'fixed') {
                    $job->labor_cost = $calculation['total'];
                }
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

    public function getStatusLabelAttribute(): string
    {
        return $this->statusLabel();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => (string) $this->status,
        };
    }

    /** Tono de la píldora de estado (DESIGN.md §3). */
    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            // Terminado o cancelado: no pide acción, no lleva color (DESIGN.md §3).
            self::STATUS_COMPLETED => 'neutral',
            self::STATUS_CANCELLED => 'neutral',
            default => 'neutral',
        };
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
