<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'service_order_id',
        'amount',
        'invoice_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function statusLabel(): string
    {
        return $this->isCancelled() ? 'Anulada' : 'Vigente';
    }

    /** Tono de la píldora de estado (DESIGN.md §3). */
    public function statusTone(): string
    {
        // Lo normal (vigente) va sin color; solo la anulada resalta.
        return $this->isCancelled() ? 'danger' : 'neutral';
    }

    /**
     * Facturas vigentes (no anuladas).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }
}
