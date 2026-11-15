<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\CashDay;
use App\Models\CashMovement;
use App\Models\Mechanic;
use App\Services\CashService;
use App\Support\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Caja diaria: base, gastos y pagos del día, cierre con el efectivo contado e historial.
 */
class CashController extends Controller
{
    private const MESSAGES = [
        'concept.required' => 'Escribe en qué se fue la plata (o de dónde vino).',
        'amount.required' => 'Escribe el valor.',
        'amount.min' => 'El valor debe ser mayor que cero.',
        'mechanic_id.required_if' => 'Elige el mecánico al que se le pagó.',
        'counted_cash.required' => 'Escribe cuánto efectivo hay en el cajón.',
        'date.before_or_equal' => 'No se puede registrar caja de un día que no ha llegado.',
    ];

    public function __construct(private readonly CashService $cash)
    {
    }

    public function index(Request $request)
    {
        $branchId = $this->requireCurrentBranchId();
        $request->validate(['date' => 'nullable|date|before_or_equal:today'], self::MESSAGES);
        $date = $request->filled('date') ? CarbonImmutable::parse($request->input('date'))->startOfDay() : CarbonImmutable::today();

        $summary = $this->cash->summary($branchId, $date);
        $balances = $this->cash->mechanicBalances($branchId);
        // A un mecánico que ya no está también hay que poder pagarle lo que se le debe.
        $owedIds = $balances->filter(fn (array $row) => $row['balance'] >= 1)->map(fn (array $row) => $row['mechanic']->id);
        $mechanics = Mechanic::forBranch($branchId)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $owedIds))
            ->orderBy('name')
            ->get(['id', 'name']);
        $startDate = $this->cash->startDate($branchId);

        // Un día cerrado guarda sus cifras; si después entró o se anuló algo, se avisa.
        $liveExpected = $summary['cash']['expected'];
        $changedAfterClose = $summary['day']?->isClosed()
            && abs((float) $summary['day']->expected_cash - $liveExpected) >= 0.5;

        // Un día cerrado se muestra con las cifras de su cierre, que suman su total; lo que cambió después lo dice el aviso.
        if ($summary['day']?->isClosed() && is_array($summary['day']->closing_summary)) {
            $closing = $summary['day']->closing_summary;
            $summary['opening'] = (float) $closing['opening'];
            // El JSON guarda 20000.0 como 20000: se devuelven decimales, como en vivo.
            $summary['cash'] = ['out_by_type' => collect($closing['cash']['out_by_type'] ?? [])->map(fn ($amount) => (float) $amount)]
                + array_map('floatval', array_diff_key($closing['cash'], ['out_by_type' => true]));
            $summary['other_methods'] = collect($closing['other_methods'] ?? []);
            $summary['counts'] = $closing['counts'] ?? $summary['counts'];
        }

        // Base que no coincide con lo que quedó al cerrar el día anterior: se avisa con botón para corregirla (decisión 3).
        $openingMismatches = $this->cash->openingMismatches($branchId, $date);

        return view('cash.index', compact('summary', 'mechanics', 'balances', 'startDate', 'date', 'changedAfterClose', 'liveExpected', 'openingMismatches'));
    }

    public function history()
    {
        $branchId = $this->requireCurrentBranchId();

        $days = CashDay::where('branch_id', $branchId)
            ->with('closedBy')
            ->orderByDesc('date')
            ->paginate(31);

        return view('cash.history', compact('days'));
    }

    public function updateOpening(Request $request)
    {
        $branchId = $this->requireCurrentBranchId();
        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'opening_cash' => 'required|numeric|min:0|max:999999999',
        ], self::MESSAGES);

        return $this->attempt($validated['date'], function (CarbonImmutable $date) use ($branchId, $validated) {
            $this->cash->setOpening($branchId, $date, (float) $validated['opening_cash']);

            return 'Base del día guardada.';
        });
    }

    public function storeMovement(Request $request)
    {
        $branchId = $this->requireCurrentBranchId();
        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'type' => ['required', Rule::in(CashMovement::MANUAL_TYPES)],
            'concept' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1|max:999999999',
            'payment_method' => 'required|string|' . PaymentMethod::rule(),
            'mechanic_id' => 'nullable|required_if:type,' . CashMovement::MECHANIC_PAYMENT . '|integer',
        ], self::MESSAGES);

        return $this->attempt($validated['date'], function (CarbonImmutable $date) use ($branchId, $validated) {
            $movement = $this->cash->addMovement($branchId, $date, $validated, auth()->id());

            return $movement->typeLabel() . ' de ' . money($movement->amount) . ' registrado.';
        });
    }

    public function destroyMovement(CashMovement $movement)
    {
        $this->ensureCurrentBranch($movement->cashDay?->branch_id);

        return $this->attempt($movement->cashDay->date->toDateString(), function () use ($movement) {
            $this->cash->deleteMovement($movement);

            return 'Movimiento eliminado.';
        });
    }

    public function close(Request $request)
    {
        $branchId = $this->requireCurrentBranchId();
        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'counted_cash' => 'required|numeric|min:0|max:999999999',
            'withdrawn_cash' => 'nullable|numeric|min:0|max:999999999',
            'closing_notes' => 'nullable|string|max:1000',
        ], self::MESSAGES);

        return $this->attempt($validated['date'], function (CarbonImmutable $date) use ($branchId, $validated) {
            $day = $this->cash->close(
                $branchId,
                $date,
                (float) $validated['counted_cash'],
                (float) ($validated['withdrawn_cash'] ?? 0),
                $validated['closing_notes'] ?? null,
                auth()->id(),
            );

            // Cuadrar es un éxito; con diferencia, el mensaje no va en verde.
            return [
                $day->differenceTone() === 'neutral' ? 'success' : 'info',
                'Caja cerrada: ' . mb_strtolower($day->differenceLabel()) . '.',
            ];
        });
    }

    public function reopen(CashDay $cashDay)
    {
        $this->ensureCurrentBranch($cashDay->branch_id);
        $this->cash->reopen($cashDay);

        return redirect()->route('cash.index', ['date' => $cashDay->date->toDateString()])
            ->with('info', 'Caja del ' . $cashDay->date->format('d/m/Y') . ' reabierta. Vuelve a cerrarla cuando termines.');
    }

    /** Ejecuta la acción y vuelve a la caja de ese día con el mensaje ([tipo, texto] o texto de éxito) o el error de negocio. */
    private function attempt(string $date, callable $action)
    {
        $date = CarbonImmutable::parse($date)->startOfDay();
        $back = route('cash.index', $date->isToday() ? [] : ['date' => $date->toDateString()]);

        try {
            $result = $action($date);
        } catch (BusinessRuleException $exception) {
            return redirect($back)->withInput()->with('error', $exception->getMessage());
        }

        [$type, $message] = is_array($result) ? $result : ['success', $result];

        return redirect($back)->with($type, $message);
    }
}
