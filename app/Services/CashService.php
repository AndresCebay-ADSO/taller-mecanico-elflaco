<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Batch;
use App\Models\CashDay;
use App\Models\CashMovement;
use App\Models\Invoice;
use App\Models\Mechanic;
use App\Models\Sale;
use App\Support\PaymentMethod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Caja diaria de una sucursal.
 *
 * Efectivo esperado = base + ventas y comprobantes en efectivo + ingresos extra en efectivo
 *                     − gastos, pagos, compras y retiros en efectivo.
 * Ventas y comprobantes se leen de sus tablas (no se duplican); lo demás son CashMovement.
 * Lo pagado por Nequi, transferencia, etc. se muestra aparte: no pasa por el cajón.
 */
class CashService
{
    /**
     * Todo lo que muestra la pantalla de caja de un día. No crea registros.
     *
     * @return array<string, mixed>
     */
    public function summary(int $branchId, CarbonImmutable $date): array
    {
        $day = $this->find($branchId, $date);
        $movements = $day
            ? $day->movements()->with(['mechanic', 'user', 'batch.product'])->latest('id')->get()
            : collect();

        $sales = Sale::forBranch($branchId)
            ->notCancelled()
            ->onDates($date, $date)
            ->select('payment_method', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as items'))
            ->groupBy('payment_method')
            ->get();

        $invoices = Invoice::active()
            ->forBranch($branchId)
            ->onDates($date, $date)
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as items'))
            ->groupBy('payment_method')
            ->get();

        // Por método: lo que entró (ventas, comprobantes, ingresos extra) y lo que salió.
        $methods = [];
        $row = function (string $label) use (&$methods) {
            return $methods[$label] ??= ['method' => $label, 'sales' => 0.0, 'invoices' => 0.0, 'income' => 0.0, 'out' => 0.0];
        };
        foreach ($sales as $sale) {
            $label = PaymentMethod::label($sale->payment_method);
            $row($label);
            $methods[$label]['sales'] += (float) $sale->total;
        }
        foreach ($invoices as $invoice) {
            $label = PaymentMethod::label($invoice->payment_method);
            $row($label);
            $methods[$label]['invoices'] += (float) $invoice->total;
        }
        foreach ($movements as $movement) {
            $label = PaymentMethod::label($movement->payment_method);
            $row($label);
            $methods[$label][$movement->isIncome() ? 'income' : 'out'] += (float) $movement->amount;
        }
        foreach ($methods as &$method) {
            $method['net'] = $method['sales'] + $method['invoices'] + $method['income'] - $method['out'];
        }
        unset($method);

        $cash = $methods[PaymentMethod::CASH] ?? ['sales' => 0.0, 'invoices' => 0.0, 'income' => 0.0, 'out' => 0.0, 'net' => 0.0];
        $opening = $day ? (float) $day->opening_cash : $this->suggestedOpening($branchId, $date);

        $cashOutByType = $movements
            ->filter(fn (CashMovement $movement) => !$movement->isIncome() && $movement->payment_method === PaymentMethod::CASH)
            ->groupBy('type')
            ->map(fn (Collection $group) => (float) $group->sum('amount'));

        $otherMethods = collect($methods)->except(PaymentMethod::CASH)->sortByDesc('net')->values();

        return [
            'date' => $date,
            'day' => $day,
            'movements' => $movements,
            'opening' => $opening,
            'cash' => [
                'sales' => $cash['sales'],
                'invoices' => $cash['invoices'],
                'income' => $cash['income'],
                'out' => $cash['out'],
                'out_by_type' => $cashOutByType,
                'expected' => $opening + $cash['net'],
            ],
            'other_methods' => $otherMethods,
            'counts' => [
                'sales' => (int) $sales->sum('items'),
                'invoices' => (int) $invoices->sum('items'),
            ],
            'unclosed_before' => CashDay::where('branch_id', $branchId)
                ->whereNull('closed_at')
                ->where('date', '<', $date->toDateString())
                ->orderByDesc('date')
                ->pluck('date'),
        ];
    }

    /** Base sugerida: lo que quedó en el cajón al cerrar el último día anterior. */
    public function suggestedOpening(int $branchId, CarbonImmutable $date): float
    {
        $previous = CashDay::where('branch_id', $branchId)
            ->whereNotNull('closed_at')
            ->where('date', '<', $date->toDateString())
            ->orderByDesc('date')
            ->first();

        return $previous ? (float) $previous->cashLeft() : 0.0;
    }

    /**
     * Días cuya base no coincide con lo que quedó al cerrar el día anterior (decisión 3), vistos desde `$date`:
     * como día siguiente (su base) y como día cerrado (la base del día que sigue). Pasa cuando se reabre y se vuelve
     * a cerrar un día después de empezar el siguiente, o cuando se cierra tarde. No se corrige sola: se avisa.
     *
     * @return Collection<int, array{previous: CashDay, next: CashDay, left: float}>
     */
    public function openingMismatches(int $branchId, CarbonImmutable $date): Collection
    {
        $mismatch = function (?CashDay $previous, ?CashDay $next): ?array {
            if (!$previous || !$next || abs((float) $next->opening_cash - $previous->cashLeft()) < 0.5) {
                return null;
            }

            return ['previous' => $previous, 'next' => $next, 'left' => $previous->cashLeft()];
        };

        $day = $this->find($branchId, $date);
        if (!$day) {
            return collect();
        }

        $previous = CashDay::where('branch_id', $branchId)
            ->whereNotNull('closed_at')
            ->where('date', '<', $date->toDateString())
            ->orderByDesc('date')
            ->first();

        $next = $day->isClosed()
            ? CashDay::where('branch_id', $branchId)->where('date', '>', $date->toDateString())->orderBy('date')->first()
            : null;

        return collect([$mismatch($previous, $day), $mismatch($day->isClosed() ? $day : null, $next)])->filter()->values();
    }

    public function setOpening(int $branchId, CarbonImmutable $date, float $amount): CashDay
    {
        return DB::transaction(function () use ($branchId, $date, $amount) {
            $day = $this->openDayForUpdate($branchId, $date);
            $day->update(['opening_cash' => $amount]);

            return $day;
        });
    }

    /**
     * @param  array{type: string, concept: string, amount: float|int|string, payment_method: string, mechanic_id?: int|null, batch_id?: int|null}  $data
     */
    public function addMovement(int $branchId, CarbonImmutable $date, array $data, ?int $userId): CashMovement
    {
        if (!array_key_exists($data['type'], CashMovement::TYPE_LABELS)) {
            throw new BusinessRuleException('Tipo de movimiento no válido.', 'type');
        }
        if (!in_array($data['payment_method'], PaymentMethod::ALL, true)) {
            throw new BusinessRuleException('Elige un método de pago de la lista.', 'payment_method');
        }
        if ((float) $data['amount'] <= 0) {
            throw new BusinessRuleException('El valor debe ser mayor que cero.', 'amount');
        }

        $mechanicId = $data['type'] === CashMovement::MECHANIC_PAYMENT ? ($data['mechanic_id'] ?? null) : null;
        if ($data['type'] === CashMovement::MECHANIC_PAYMENT
            && !Mechanic::forBranch($branchId)->whereKey($mechanicId)->exists()) {
            throw new BusinessRuleException('Elige el mecánico al que se le pagó.', 'mechanic_id');
        }

        return DB::transaction(function () use ($branchId, $date, $data, $userId, $mechanicId) {
            $day = $this->openDayForUpdate($branchId, $date);

            return $day->movements()->create([
                'type' => $data['type'],
                'concept' => $data['concept'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'mechanic_id' => $mechanicId,
                'batch_id' => $data['batch_id'] ?? null,
                'user_id' => $userId,
            ]);
        });
    }

    /** Compra de repuestos pagada con plata del día (se llama desde Inventario → Registrar compra). */
    public function recordPurchase(int $branchId, Batch $batch, string $paymentMethod, ?int $userId): CashMovement
    {
        return $this->addMovement($branchId, CarbonImmutable::today(), [
            'type' => CashMovement::PURCHASE,
            'concept' => $this->purchaseConcept($batch),
            'amount' => (float) $batch->cost_price * $batch->quantity,
            'payment_method' => $paymentMethod,
            'batch_id' => $batch->id,
        ], $userId);
    }

    /**
     * Al corregir un lote (costo, cantidad o proveedor), la salida de caja de esa compra se ajusta.
     * Si el valor cambia y la caja de ese día ya se cerró, no se corrige: primero hay que reabrirla.
     *
     * @throws BusinessRuleException
     */
    public function syncPurchase(Batch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $movement = CashMovement::where('batch_id', $batch->id)
                ->where('type', CashMovement::PURCHASE)
                ->lockForUpdate()
                ->first();

            if (!$movement) {
                return;
            }

            $batch->unsetRelation('supplier')->loadMissing(['product', 'supplier']);
            $amount = (float) $batch->cost_price * $batch->quantity;
            $day = CashDay::whereKey($movement->cash_day_id)->lockForUpdate()->firstOrFail();

            if (abs($amount - (float) $movement->amount) >= 0.005 && $day->isClosed()) {
                throw new BusinessRuleException(
                    'Esta compra se pagó con la caja del ' . $day->date->format('d/m/Y') . ', que ya está cerrada. Reábrela para corregir el costo o la cantidad.',
                    'cost_price'
                );
            }

            $movement->update(['amount' => $amount, 'concept' => $this->purchaseConcept($batch)]);
        });
    }

    public function closedToday(int $branchId): bool
    {
        return (bool) $this->find($branchId, CarbonImmutable::today())?->isClosed();
    }

    /**
     * Con la caja de hoy cerrada no se reciben pagos en efectivo (decisión 1): se anotan en el cuaderno y se
     * registran mañana, con la plata guardada en el cajón. Los demás métodos no tocan el cajón y sí se aceptan.
     *
     * @throws BusinessRuleException
     */
    public function ensureCashAccepted(int $branchId, string $paymentMethod): void
    {
        if ($paymentMethod === PaymentMethod::CASH && $this->closedToday($branchId)) {
            throw new BusinessRuleException(self::CLOSED_TODAY_MESSAGE, 'payment_method');
        }
    }

    public const CLOSED_TODAY_MESSAGE = 'La caja de hoy ya se cerró, así que no se reciben pagos en efectivo. '
        . 'Anótalo en el cuaderno, deja la plata en el cajón y regístralo mañana. '
        . 'Si necesitas registrarlo hoy, reabre la caja.';

    /**
     * Devolución a cliente al anular una venta o factura en efectivo de un día anterior (decisión 2):
     * sale de la caja de hoy, ligada al documento anulado. Se llama en la misma transacción que la anulación.
     *
     * @throws BusinessRuleException si la caja de hoy ya está cerrada
     */
    public function recordRefund(int $branchId, Sale|Invoice $document, ?int $userId): CashMovement
    {
        if ($this->closedToday($branchId)) {
            throw new BusinessRuleException(
                'La caja de hoy ya está cerrada, así que no puede salir de ahí la devolución. '
                . 'Reábrela, o anula sin devolver plata.',
                'refunded'
            );
        }

        $isSale = $document instanceof Sale;

        return DB::transaction(function () use ($branchId, $document, $isSale, $userId) {
            $day = $this->openDayForUpdate($branchId, CarbonImmutable::today());

            return $day->movements()->create([
                'type' => CashMovement::REFUND,
                'concept' => $isSale
                    ? 'Venta ' . $document->displayNumber() . ' anulada'
                    : 'Factura ' . $document->invoice_number . ' anulada',
                'amount' => $isSale ? $document->total_amount : $document->amount,
                'payment_method' => PaymentMethod::CASH,
                'sale_id' => $isSale ? $document->id : null,
                'invoice_id' => $isSale ? null : $document->id,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Aviso al anular una venta o comprobante en efectivo de hoy con la caja de hoy ya cerrada.
     * Los de días anteriores no avisan: preguntan si se devolvió la plata (decisión 2).
     */
    public function closedDayNotice(int $branchId, CarbonInterface $date, ?string $paymentMethod): ?string
    {
        if ($paymentMethod !== PaymentMethod::CASH || !$date->isToday() || !$this->closedToday($branchId)) {
            return null;
        }

        return 'La caja de hoy ya estaba cerrada y esto cambia el efectivo que debe haber. Hay que reabrir la caja y cerrarla de nuevo.';
    }

    private function purchaseConcept(Batch $batch): string
    {
        $batch->loadMissing(['product', 'supplier']);

        return $batch->product->name . ' × ' . $batch->quantity . ($batch->supplier ? ' (' . $batch->supplier->name . ')' : '');
    }

    public function deleteMovement(CashMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            $day = CashDay::whereKey($movement->cash_day_id)->lockForUpdate()->firstOrFail();
            if ($day->isClosed()) {
                throw new BusinessRuleException('La caja de ese día ya está cerrada. Reábrela para cambiarla.', 'movement');
            }
            $movement->delete();
        });
    }

    public function close(int $branchId, CarbonImmutable $date, float $counted, float $withdrawn, ?string $notes, ?int $userId): CashDay
    {
        if ($withdrawn > $counted) {
            throw new BusinessRuleException('No se puede retirar más de lo que hay en el cajón.', 'withdrawn_cash');
        }

        return DB::transaction(function () use ($branchId, $date, $counted, $withdrawn, $notes, $userId) {
            $day = $this->openDayForUpdate($branchId, $date);
            $summary = $this->summary($branchId, $date);

            $day->update([
                'closed_at' => now(),
                'closed_by' => $userId,
                'expected_cash' => $summary['cash']['expected'],
                'counted_cash' => $counted,
                'withdrawn_cash' => $withdrawn,
                'closing_notes' => $notes,
                'closing_summary' => [
                    'opening' => $summary['opening'],
                    'cash' => collect($summary['cash'])->except('out_by_type')->all() + ['out_by_type' => $summary['cash']['out_by_type']->all()],
                    'other_methods' => $summary['other_methods']->all(),
                    'counts' => $summary['counts'],
                ],
            ]);

            return $day;
        }, 3);
    }

    public function reopen(CashDay $day): void
    {
        $day->update([
            'closed_at' => null,
            'closed_by' => null,
            'expected_cash' => null,
            'counted_cash' => null,
            'withdrawn_cash' => null,
            'closing_summary' => null,
            'closing_notes' => null,
        ]);
    }

    /**
     * Comisiones por pagar de cada mecánico: lo ganado en trabajos completados desde que se empezó
     * a usar la caja en la sucursal, menos lo pagado desde la caja. Antes de esa fecha no hay
     * registro de pagos, así que no se cuenta.
     *
     * @return Collection<int, array{mechanic: Mechanic, earned: float, paid: float, balance: float}>
     */
    public function mechanicBalances(int $branchId): Collection
    {
        $start = CashDay::where('branch_id', $branchId)->min('date');
        if ($start === null) {
            return collect();
        }

        $paid = CashMovement::query()
            ->join('cash_days', 'cash_days.id', '=', 'cash_movements.cash_day_id')
            ->where('cash_days.branch_id', $branchId)
            ->where('cash_movements.type', CashMovement::MECHANIC_PAYMENT)
            ->groupBy('cash_movements.mechanic_id')
            ->selectRaw('cash_movements.mechanic_id, SUM(cash_movements.amount) as total')
            ->pluck('total', 'mechanic_id');

        return Mechanic::forBranch($branchId)
            ->withSum(['workshopJobs as earned' => fn ($query) => $query
                ->completedBetween(CarbonImmutable::parse($start)->startOfDay())], 'mechanic_cost')
            ->orderBy('name')
            ->get()
            ->map(fn (Mechanic $mechanic) => [
                'mechanic' => $mechanic,
                'earned' => (float) $mechanic->earned,
                'paid' => (float) ($paid[$mechanic->id] ?? 0),
                'balance' => (float) $mechanic->earned - (float) ($paid[$mechanic->id] ?? 0),
            ])
            ->filter(fn (array $row) => $row['mechanic']->is_active || abs($row['balance']) >= 0.5)
            ->values();
    }

    public function startDate(int $branchId): ?CarbonImmutable
    {
        $start = CashDay::where('branch_id', $branchId)->min('date');

        return $start ? CarbonImmutable::parse($start) : null;
    }

    private function find(int $branchId, CarbonImmutable $date): ?CashDay
    {
        return CashDay::where('branch_id', $branchId)->whereDate('date', $date->toDateString())->first();
    }

    /** El día con bloqueo de fila; lo crea con la base sugerida si no existe. Falla si está cerrado o es futuro. */
    private function openDayForUpdate(int $branchId, CarbonImmutable $date): CashDay
    {
        if ($date->isAfter(CarbonImmutable::today())) {
            throw new BusinessRuleException('No se puede registrar caja de un día que no ha llegado.', 'date');
        }

        $day = CashDay::where('branch_id', $branchId)->whereDate('date', $date->toDateString())->lockForUpdate()->first();

        if (!$day) {
            CashDay::insertOrIgnore([
                'branch_id' => $branchId,
                'date' => $date->toDateString(),
                'opening_cash' => $this->suggestedOpening($branchId, $date),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $day = CashDay::where('branch_id', $branchId)->whereDate('date', $date->toDateString())->lockForUpdate()->firstOrFail();
        }

        if ($day->isClosed()) {
            throw new BusinessRuleException('La caja del ' . $date->format('d/m/Y') . ' ya está cerrada.', 'cash');
        }

        return $day;
    }
}
