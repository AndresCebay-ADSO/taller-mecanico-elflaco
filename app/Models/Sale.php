<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Support\PaymentMethod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    use BelongsToBranch;

    public const STATUS_CANCELLED = 'anulada';

    protected $fillable = [
        'customer_name',
        'total_amount',
        'branch_id',
        'sale_date',
        'payment_method',
        'user_id',
        'status',
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getTotalItemsAttribute()
    {
        return $this->saleProducts->sum('quantity');
    }

    /** Ventas que cuentan: las no anuladas. */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /** Ventas con fecha de venta entre esos dos días (incluidos). */
    public function scopeOnDates(Builder $query, DateTimeInterface|string $from, DateTimeInterface|string $to): Builder
    {
        return $query->whereDate('sale_date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('sale_date', '<=', Carbon::parse($to)->toDateString());
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function statusLabel(): string
    {
        return $this->isCancelled() ? 'Anulada' : 'Completada';
    }

    /** Tono de la píldora de estado (DESIGN.md §3). */
    public function statusTone(): string
    {
        // Lo normal (completada) va sin color; solo la anulada resalta.
        return $this->isCancelled() ? 'danger' : 'neutral';
    }

    /** Al anularla se pregunta si se devolvió la plata: efectivo de un día anterior (decisión 2). */
    public function asksForRefund(): bool
    {
        return !$this->isCancelled()
            && $this->payment_method === PaymentMethod::CASH
            && $this->sale_date->lt(today());
    }

    /** Número visible de la venta: #S0042. */
    public function displayNumber(): string
    {
        return '#S' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
