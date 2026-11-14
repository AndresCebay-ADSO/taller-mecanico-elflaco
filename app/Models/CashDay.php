<?php

namespace App\Models;

use App\Support\Tone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * La caja de un día en una sucursal. Ver App\Services\CashService.
 */
class CashDay extends Model
{
    protected $fillable = [
        'branch_id',
        'date',
        'opening_cash',
        'closed_at',
        'closed_by',
        'expected_cash',
        'counted_cash',
        'withdrawn_cash',
        'closing_summary',
        'closing_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_cash' => 'decimal:2',
        'closed_at' => 'datetime',
        'expected_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'withdrawn_cash' => 'decimal:2',
        'closing_summary' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /** Contado − esperado: negativo = falta plata, positivo = sobra. */
    public function difference(): ?float
    {
        return $this->isClosed() ? (float) $this->counted_cash - (float) $this->expected_cash : null;
    }

    /** Lo que queda en el cajón para el día siguiente. */
    public function cashLeft(): ?float
    {
        return $this->isClosed() ? (float) $this->counted_cash - (float) $this->withdrawn_cash : null;
    }

    public function differenceLabel(): string
    {
        $difference = $this->difference();

        return match (true) {
            $difference === null => 'Abierta',
            abs($difference) < 0.5 => 'Cuadró',
            $difference < 0 => 'Faltan ' . money(abs($difference)),
            default => 'Sobran ' . money($difference),
        };
    }

    /** Cuadrar es lo normal (sin color); faltante en rojo, sobrante en ámbar (DESIGN.md §3). */
    public function differenceTone(): string
    {
        $difference = $this->difference();

        return match (true) {
            $difference === null, abs($difference) < 0.5 => Tone::NEUTRAL,
            $difference < 0 => Tone::DANGER,
            default => Tone::WARNING,
        };
    }
}
