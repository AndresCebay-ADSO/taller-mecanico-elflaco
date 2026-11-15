<?php

namespace App\Models;

use App\Support\PaymentMethod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'service_order_id',
        'amount',
        'line_items',
        'payment_method',
        'invoice_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'cancelled_at' => 'datetime',
        'line_items' => 'array',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /**
     * Líneas de la factura: la copia guardada al emitirla (decisión 11). Las facturas anteriores a esa copia
     * leen los trabajos actuales de su orden.
     *
     * @return Collection<int, array{service: string, mechanic: string, description: ?string, labor: float, parts: array<int, array{name: string, quantity: int, unit_price: float, total_price: float}>}>
     */
    public function lineItems(): Collection
    {
        if ($this->line_items !== null) {
            // El JSON guarda 40000.0 como 40000: se devuelven los mismos tipos que linesFromJobs().
            return collect($this->line_items)->map(fn (array $line) => [
                'service' => (string) $line['service'],
                'mechanic' => (string) $line['mechanic'],
                'description' => $line['description'],
                'labor' => (float) $line['labor'],
                'parts' => array_map(fn (array $part) => [
                    'name' => (string) $part['name'],
                    'quantity' => (int) $part['quantity'],
                    'unit_price' => (float) $part['unit_price'],
                    'total_price' => (float) $part['total_price'],
                ], $line['parts']),
            ]);
        }

        $this->loadMissing([
            'serviceOrder.workshopJobs.jobType',
            'serviceOrder.workshopJobs.mechanic',
            'serviceOrder.workshopJobs.jobProducts.product' => fn ($query) => $query->withTrashed(),
        ]);

        return self::linesFromJobs($this->serviceOrder->workshopJobs->where('status', '!=', WorkshopJob::STATUS_CANCELLED));
    }

    /**
     * Trabajos (con jobType, mechanic y jobProducts.product cargados) en el formato de las líneas de la factura.
     *
     * @param  Collection<int, WorkshopJob>  $jobs
     */
    public static function linesFromJobs(Collection $jobs): Collection
    {
        return $jobs->values()->map(fn (WorkshopJob $job) => [
            'service' => $job->jobType?->name ?? 'Servicio',
            'mechanic' => $job->mechanic?->name ?? 'Sin asignar',
            'description' => $job->description,
            'labor' => (float) $job->total_amount,
            'parts' => $job->jobProducts->map(fn (JobProduct $part) => [
                'name' => $part->product?->name ?? 'Producto eliminado',
                'quantity' => (int) $part->quantity,
                'unit_price' => (float) $part->unit_price,
                'total_price' => (float) $part->total_price,
            ])->values()->all(),
        ]);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function paymentMethodLabel(): string
    {
        return PaymentMethod::label($this->payment_method);
    }

    /** Al anularla se pregunta si se devolvió la plata: efectivo de un día anterior (decisión 2). */
    public function asksForRefund(): bool
    {
        return !$this->isCancelled()
            && $this->payment_method === PaymentMethod::CASH
            && $this->invoice_date->lt(today());
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

    /** Salida «Devolución a cliente» de la caja, si al anularla se devolvió la plata (decisión 2). */
    public function refundMovement(): HasOne
    {
        return $this->hasOne(CashMovement::class, 'invoice_id');
    }

    /** Las que cuentan en la caja de su día: las vigentes y las anuladas con devolución (ver Sale::scopeInCashRegister). */
    public function scopeInCashRegister(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereNull('cancelled_at')
            ->orWhereHas('refundMovement'));
    }

    /** Facturas de órdenes de esa sucursal (la factura no guarda la sucursal: la toma de su orden). */
    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->whereHas('serviceOrder', fn (Builder $order) => $order->forBranch($branchId));
    }

    /** Facturas con fecha entre esos dos días (incluidos). */
    public function scopeOnDates(Builder $query, DateTimeInterface|string $from, DateTimeInterface|string $to): Builder
    {
        return $query->whereDate('invoice_date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('invoice_date', '<=', Carbon::parse($to)->toDateString());
    }
}
