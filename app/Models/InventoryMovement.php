<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'batch_id',
        'movement_type',
        'quantity',
        'unit_price',
        'supplier_id',
        'reference',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /** Nombre visible de cada tipo de movimiento. */
    public const TYPE_LABELS = [
        'purchase' => 'Compra',
        'sale' => 'Venta',
        'job_usage' => 'Uso en orden',
        'adjustment' => 'Ajuste',
        'reversal' => 'Anulación',
        'transfer_out' => 'Transferencia enviada',
        'transfer_in' => 'Transferencia recibida',
    ];

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->movement_type] ?? (string) $this->movement_type;
    }

    /** Dirección (DESIGN.md §3): entrada success, salida warning, transferencia info. */
    public function directionTone(): string
    {
        return match (true) {
            str_starts_with((string) $this->movement_type, 'transfer') => 'info',
            $this->quantity > 0 => 'success',
            default => 'warning',
        };
    }

    public function directionIcon(): string
    {
        return match (true) {
            str_starts_with((string) $this->movement_type, 'transfer') => 'arrow-left-right',
            $this->quantity > 0 => 'arrow-down',
            default => 'arrow-up',
        };
    }

    /** Cantidad con signo: +5 / −5 (signo menos tipográfico). */
    public function signedQuantity(): string
    {
        return ($this->quantity > 0 ? '+' : '−') . abs((int) $this->quantity);
    }
}
