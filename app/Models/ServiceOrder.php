<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

class ServiceOrder extends Model
{
    use BelongsToBranch;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Cliente y moto: iguales al crear o editar una orden y al crear un trabajo individual (que crea su orden).
     * Si no coinciden, una orden creada por un lado no se puede editar por el otro.
     */
    public const CUSTOMER_RULES = [
        'customer_name' => 'required|string|max:255',
        'customer_phone' => 'nullable|digits:10',
        'vehicle_info' => 'required|string|max:255',
    ];

    /** Formulario de la orden: el cliente y la moto, más el documento y el motivo de ingreso. */
    public const FORM_RULES = self::CUSTOMER_RULES + [
        'customer_document' => 'nullable|string|max:20',
        'service_description' => 'required|string|max:5000',
    ];

    protected $fillable = [
        'customer_name',
        'customer_document',
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

    /**
     * Todas las facturas de la orden, incluidas las anuladas.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * La factura vigente: como máximo una por orden (InvoiceService lo garantiza).
     */
    public function activeInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->whereNull('cancelled_at');
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

    /** Número visible de la orden: #0042. */
    public function displayNumber(): string
    {
        return '#' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
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

    public function canReceiveJobs(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
