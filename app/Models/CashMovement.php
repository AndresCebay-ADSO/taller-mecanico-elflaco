<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada o salida de caja distinta de ventas y comprobantes (esos se leen de sus tablas).
 */
class CashMovement extends Model
{
    public const EXPENSE = 'expense';
    public const MECHANIC_PAYMENT = 'mechanic';
    public const PURCHASE = 'purchase';
    public const WITHDRAWAL = 'withdrawal';
    public const INCOME = 'income';
    /** Plata devuelta al anular una venta o factura de un día anterior (decisión 2). Solo nace al anular. */
    public const REFUND = 'refund';

    /** Tipos que se registran a mano en la pantalla de caja (la compra se registra desde Inventario). */
    public const TYPE_LABELS = [
        self::EXPENSE => 'Gasto',
        self::MECHANIC_PAYMENT => 'Pago a mecánico',
        self::PURCHASE => 'Compra de repuestos',
        self::WITHDRAWAL => 'Retiro',
        self::INCOME => 'Ingreso extra',
        self::REFUND => 'Devolución a cliente',
    ];

    public const MANUAL_TYPES = [self::EXPENSE, self::MECHANIC_PAYMENT, self::WITHDRAWAL, self::INCOME];

    protected $fillable = [
        'cash_day_id',
        'type',
        'concept',
        'amount',
        'payment_method',
        'mechanic_id',
        'batch_id',
        'sale_id',
        'invoice_id',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashDay(): BelongsTo
    {
        return $this->belongsTo(CashDay::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Nació de otro registro (compra o anulación): borrarlo de la caja no cambia ese registro. */
    public function isLinked(): bool
    {
        return $this->batch_id !== null || $this->sale_id !== null || $this->invoice_id !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isIncome(): bool
    {
        return $this->type === self::INCOME;
    }

    /** Monto con signo: + entra a la caja, − sale. */
    public function signedAmount(): float
    {
        return $this->isIncome() ? (float) $this->amount : -(float) $this->amount;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
