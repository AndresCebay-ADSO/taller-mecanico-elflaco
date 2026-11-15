@php
    use App\Models\CashMovement;
    use App\Support\PaymentMethod;

    $day = $summary['day'];
    $cash = $summary['cash'];
    $isClosed = $day?->isClosed() ?? false;
    $isToday = $date->isToday();
    $title = $isToday ? 'Caja de hoy' : 'Caja del ' . $date->format('d/m/Y');
    $outLines = [
        CashMovement::EXPENSE => 'Gastos',
        CashMovement::MECHANIC_PAYMENT => 'Pagos a mecánicos',
        CashMovement::PURCHASE => 'Compras de repuestos',
        CashMovement::WITHDRAWAL => 'Retiros',
        CashMovement::REFUND => 'Devoluciones a clientes',
    ];
    $isAdmin = auth()->user()->can('admin');
@endphp

<x-app-layout>
<div class="ds">
    <x-page-header :title="$title" :subtitle="ucfirst($date->translatedFormat('l j \d\e F \d\e Y'))">
        <x-slot name="badge">
            @if($isClosed)
                <x-status-badge :tone="$day->differenceTone()">Cerrada · {{ $day->differenceLabel() }}</x-status-badge>
            @else
                <x-status-badge>Abierta</x-status-badge>
            @endif
        </x-slot>
        <x-slot name="actions">
            <x-button variant="secondary" size="icon" href="{{ route('cash.index', ['date' => $date->subDay()->toDateString()]) }}" title="Día anterior">
                <i data-lucide="chevron-left" class="size-4"></i>
                <span class="sr-only">Día anterior</span>
            </x-button>
            <form method="GET" action="{{ route('cash.index') }}">
                <label for="cash-date" class="sr-only">Ver otro día</label>
                <input id="cash-date" type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                       onchange="this.form.submit()"
                       class="h-9 rounded-md border border-input bg-card px-3 text-sm text-foreground shadow-xs focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
            </form>
            @unless($isToday)
                <x-button variant="secondary" size="icon" href="{{ route('cash.index', $date->addDay()->isToday() ? [] : ['date' => $date->addDay()->toDateString()]) }}" title="Día siguiente">
                    <i data-lucide="chevron-right" class="size-4"></i>
                    <span class="sr-only">Día siguiente</span>
                </x-button>
                <x-button variant="secondary" href="{{ route('cash.index') }}">Hoy</x-button>
            @endunless
            <x-button variant="secondary" href="{{ route('cash.history') }}">
                <i data-lucide="history" class="size-4"></i>
                Historial
            </x-button>
        </x-slot>
    </x-page-header>

    @if($summary['unclosed_before']->isNotEmpty())
        @php $lastUnclosed = $summary['unclosed_before']->first(); @endphp
        <div class="mb-6">
            <x-alert type="warning">
                {{ $summary['unclosed_before']->count() === 1 ? 'Quedó un día sin cerrar:' : 'Quedaron ' . $summary['unclosed_before']->count() . ' días sin cerrar; el último:' }}
                <a href="{{ route('cash.index', ['date' => $lastUnclosed->toDateString()]) }}" class="font-medium underline">{{ $lastUnclosed->format('d/m/Y') }}</a>.
                Ciérralo para que la base de hoy salga bien.
            </x-alert>
        </div>
    @endif

    @if($changedAfterClose)
        <div class="mb-6">
            <x-alert type="warning">
                Después del cierre cambió lo que debería haber en efectivo: ahora {{ money($liveExpected) }} (al cerrar era {{ money($day->expected_cash) }}).
                Puede ser una venta registrada después de cerrar o algo anulado.@if($isAdmin) Si hace falta, reabre la caja y vuelve a cerrarla.@endif
            </x-alert>
        </div>
    @endif

    @foreach($openingMismatches as $mismatch)
        @php
            $next = $mismatch['next'];
            $nextIsToday = $next->date->isToday();
            $nextLabel = $nextIsToday ? 'hoy' : 'el ' . $next->date->translatedFormat('l j \d\e F');
            $nextOf = $nextIsToday ? 'de hoy' : 'del ' . $next->date->translatedFormat('l j \d\e F');
            $previousLabel = $mismatch['previous']->date->equalTo($date) ? 'este día' : 'el ' . $mismatch['previous']->date->translatedFormat('l j \d\e F');
        @endphp
        <div class="mb-6">
            <x-alert type="warning">
                <p>
                    La base {{ $nextOf }} es {{ money($next->opening_cash) }}, pero al cerrar {{ $previousLabel }} quedaron
                    {{ money($mismatch['left']) }} en el cajón. Pasa cuando se reabre y se vuelve a cerrar un día, o cuando se cierra tarde.
                </p>
                @if($next->isClosed())
                    <p class="mt-1">{{ ucfirst($nextLabel) }} ya está cerrado: para corregir su base hay que reabrirlo.</p>
                @else
                    <form method="POST" action="{{ route('cash.opening') }}" class="mt-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="date" value="{{ $next->date->toDateString() }}">
                        <input type="hidden" name="opening_cash" value="{{ round($mismatch['left'], 2) }}">
                        <x-button type="submit" size="sm" variant="secondary">Usar {{ money($mismatch['left']) }} como base {{ $nextOf }}</x-button>
                    </form>
                @endif
            </x-alert>
        </div>
    @endforeach

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Cuadre del efectivo --}}
            <x-card title="Efectivo en el cajón" description="Solo efectivo: lo de Nequi, transferencias y tarjeta va abajo">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Base con la que se abrió</dt>
                        <dd class="tabular-nums">
                            @if($isClosed)
                                {{ money($summary['opening']) }}
                            @else
                                <form method="POST" action="{{ route('cash.opening') }}" class="flex items-center gap-2" x-data="{ editing: {{ $errors->has('opening_cash') ? 'true' : 'false' }} }">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                                    <span x-show="!editing">{{ money($summary['opening']) }}</span>
                                    <button type="button" x-show="!editing" x-on:click="editing = true; $nextTick(() => $refs.opening.select())"
                                            class="cursor-pointer text-xs font-medium text-primary hover:underline">Cambiar</button>
                                    <span x-show="editing" x-cloak class="flex items-center gap-2">
                                        <input x-ref="opening" name="opening_cash" inputmode="numeric" aria-label="Base del día"
                                               value="{{ old('opening_cash', (int) round($summary['opening'])) }}"
                                               class="money-input h-8 w-32 rounded-md border border-input bg-card px-2 text-right text-sm tabular-nums focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                                        <x-button type="submit" size="sm">Guardar</x-button>
                                    </span>
                                </form>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">+ Ventas de mostrador en efectivo</dt>
                        <dd class="tabular-nums">{{ money($cash['sales']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">+ Comprobantes de servicio en efectivo</dt>
                        <dd class="tabular-nums">{{ money($cash['invoices']) }}</dd>
                    </div>
                    @if($cash['income'] > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">+ Ingresos extra</dt>
                            <dd class="tabular-nums">{{ money($cash['income']) }}</dd>
                        </div>
                    @endif
                    @foreach($outLines as $type => $label)
                        @if(($cash['out_by_type'][$type] ?? 0) > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">− {{ $label }}</dt>
                                <dd class="tabular-nums">{{ money(-$cash['out_by_type'][$type]) }}</dd>
                            </div>
                        @endif
                    @endforeach
                    <div class="flex items-baseline justify-between gap-4 border-t border-border pt-3">
                        <dt class="font-medium">Debe haber en el cajón</dt>
                        <dd class="text-2xl font-semibold tabular-nums">{{ money($isClosed ? $day->expected_cash : $cash['expected']) }}</dd>
                    </div>

                    @if($isClosed)
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Se contó</dt>
                            <dd class="font-medium tabular-nums">{{ money($day->counted_cash) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-muted-foreground">Diferencia</dt>
                            <dd><x-status-badge :tone="$day->differenceTone()">{{ $day->differenceLabel() }}</x-status-badge></dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Se sacó al cerrar</dt>
                            <dd class="tabular-nums">{{ money($day->withdrawn_cash) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-border pt-3">
                            <dt class="font-medium">Quedó de base para el día siguiente</dt>
                            <dd class="font-semibold tabular-nums">{{ money($day->cashLeft()) }}</dd>
                        </div>
                    @endif
                </dl>

                @if($isClosed)
                    <div class="mt-5 flex flex-col gap-3 border-t border-border pt-4 text-sm sm:flex-row sm:items-start sm:justify-between">
                        <div class="text-muted-foreground">
                            <p>Cerrada el {{ $day->closed_at->format('d/m/Y h:i a') }}{{ $day->closedBy ? ' por ' . $day->closedBy->name : '' }}.</p>
                            @if($day->closing_notes)
                                <p class="mt-1 text-foreground">{{ $day->closing_notes }}</p>
                            @endif
                        </div>
                        @if($isAdmin)
                            <form method="POST" action="{{ route('cash.reopen', $day) }}" class="shrink-0"
                                  data-confirm="¿Reabrir la caja del {{ $date->format('d/m/Y') }}?"
                                  data-confirm-description="Se borra el conteo y se puede volver a registrar gastos. Después hay que cerrarla de nuevo."
                                  data-confirm-label="Reabrir caja">
                                @csrf
                                <x-button type="submit" variant="secondary" size="sm">Reabrir caja</x-button>
                            </form>
                        @endif
                    </div>
                @endif
            </x-card>

            {{-- Movimientos registrados a mano (y compras desde inventario) --}}
            <x-card title="Gastos, pagos y retiros" flush>
                @if($summary['movements']->isNotEmpty())
                    <x-table :headers="['Hora', 'Concepto', 'Pago', ['label' => 'Valor', 'align' => 'right'], ['label' => '', 'align' => 'right']]">
                        @foreach($summary['movements'] as $movement)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-muted-foreground"
                                    @unless($movement->created_at->isSameDay($date)) title="Registrado el {{ $movement->created_at->format('d/m/Y h:i a') }}" @endunless>
                                    {{ $movement->created_at->isSameDay($date) ? $movement->created_at->format('h:i a') : $movement->created_at->format('d/m h:i a') }}
                                </td>
                                <td class="px-4 py-3">
                                    <p>{{ $movement->concept }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        {{ $movement->typeLabel() }}@if($movement->mechanic) · {{ $movement->mechanic->name }}@endif
                                        @if($movement->user) · {{ $movement->user->name }}@endif
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $movement->payment_method }}</td>
                                <td @class(['whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums', 'text-success-text' => $movement->isIncome()])>
                                    {{ $movement->isIncome() ? '+' : '' }}{{ money($movement->signedAmount()) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-right">
                                    @if(! $isClosed && $isAdmin)
                                        <form method="POST" action="{{ route('cash.movements.destroy', $movement) }}"
                                              data-confirm="¿Eliminar «{{ $movement->concept }}»?"
                                              data-confirm-description="{{ match (true) {
                                                  $movement->batch_id !== null => 'Solo se quita de la caja: la compra sigue registrada en el inventario.',
                                                  $movement->isLinked() => 'Solo se quita de la caja: el documento sigue anulado.',
                                                  default => 'Deja de contar en la caja de este día.',
                                              } }}"
                                              data-confirm-label="Eliminar"
                                              data-confirm-destructive>
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="ghost" size="icon" title="Eliminar">
                                                <i data-lucide="trash-2" class="size-4"></i>
                                                <span class="sr-only">Eliminar {{ $movement->concept }}</span>
                                            </x-button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <div class="p-5">
                        <x-empty-state icon="wallet">
                            No hay gastos ni pagos registrados {{ $isToday ? 'hoy' : 'este día' }}.
                        </x-empty-state>
                    </div>
                @endif
            </x-card>

            {{-- Otros medios: no pasan por el cajón --}}
            @if($summary['other_methods']->isNotEmpty())
                <x-card title="Otros medios de pago" description="No pasan por el cajón: revísalos contra la app del banco o de Nequi" flush>
                    <x-table :headers="['Medio', ['label' => 'Entró', 'align' => 'right'], ['label' => 'Salió', 'align' => 'right'], ['label' => 'Neto', 'align' => 'right']]">
                        @foreach($summary['other_methods'] as $method)
                            <tr>
                                <td class="px-4 py-3">{{ $method['method'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ money($method['sales'] + $method['invoices'] + $method['income']) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-muted-foreground">{{ $method['out'] > 0 ? money(-$method['out']) : '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums">{{ money($method['net']) }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            @unless($isClosed)
                {{-- Registrar gasto, pago, retiro o ingreso --}}
                <x-card title="Registrar salida o entrada" description="Lo que se paga o se saca de la plata del día">
                    <form method="POST" action="{{ route('cash.movements.store') }}" class="space-y-4"
                          x-data="{ type: @js(old('type', CashMovement::EXPENSE)), mechanic: @js((string) old('mechanic_id', '')) }"
                          x-on:cash-pay-mechanic.window="type = '{{ CashMovement::MECHANIC_PAYMENT }}'; mechanic = String($event.detail.id); $refs.concept.value = 'Pago de comisiones'; $refs.amount.value = $event.detail.amount; $refs.amount.focus()">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">

                        <x-select label="Tipo" name="type" required x-model="type">
                            @foreach(CashMovement::MANUAL_TYPES as $type)
                                <option value="{{ $type }}">{{ CashMovement::TYPE_LABELS[$type] }}</option>
                            @endforeach
                        </x-select>

                        <div x-show="type === '{{ CashMovement::MECHANIC_PAYMENT }}'" x-cloak>
                            <x-select label="Mecánico" name="mechanic_id" x-model="mechanic">
                                <option value="">Elige…</option>
                                @foreach($mechanics as $mechanic)
                                    <option value="{{ $mechanic->id }}">{{ $mechanic->name }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <x-input label="Concepto" name="concept" required maxlength="255" x-ref="concept"
                                 placeholder="Ej.: almuerzo, domicilio, recibo de luz" />

                        <x-input label="Valor" name="amount" is-money required placeholder="0" x-ref="amount" />

                        <x-select label="¿Cómo se pagó?" name="payment_method" required>
                            @foreach(PaymentMethod::ALL as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', PaymentMethod::CASH) === $method)>{{ $method }}</option>
                            @endforeach
                        </x-select>

                        <x-button type="submit" class="w-full">Registrar</x-button>
                        <p class="text-xs text-muted-foreground">Las compras de repuestos se registran desde Inventario → Registrar compra.</p>
                    </form>
                </x-card>

                {{-- Cierre --}}
                <x-card title="Cerrar caja" description="Cuenta el efectivo del cajón al final del día">
                    <form method="POST" action="{{ route('cash.close') }}" class="space-y-4"
                          x-data="{
                              expected: {{ (int) round($cash['expected']) }},
                              counted: @js((string) old('counted_cash', '')),
                              get diff() { const n = parseInt(String(this.counted).replace(/\D/g, ''), 10); return Number.isNaN(n) ? null : n - this.expected; },
                              fmt(n) { return '$' + Math.abs(Math.round(n)).toLocaleString('es-CO'); },
                          }"
                          data-confirm="¿Cerrar la caja del {{ $date->format('d/m/Y') }}?"
                          data-confirm-description="Después de cerrar no se pueden registrar más gastos en este día."
                          data-confirm-label="Cerrar caja">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">

                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="text-muted-foreground">Debe haber</span>
                            <span class="font-semibold tabular-nums">{{ money($cash['expected']) }}</span>
                        </div>

                        <x-input label="Efectivo contado" name="counted_cash" is-money required placeholder="0" x-model="counted" />

                        <p class="text-sm" x-show="diff !== null" x-cloak aria-live="polite">
                            <span x-show="Math.abs(diff) < 1" class="text-muted-foreground">Cuadra.</span>
                            <span x-show="diff <= -1" class="font-medium text-destructive-text">Faltan <span x-text="fmt(diff)"></span></span>
                            <span x-show="diff >= 1" class="font-medium text-warning-text">Sobran <span x-text="fmt(diff)"></span></span>
                        </p>

                        <x-input label="Se saca del cajón" name="withdrawn_cash" is-money placeholder="0"
                                 help="Lo que se lleva o se consigna al cerrar. Lo demás queda de base para mañana." />

                        <x-textarea label="Notas" name="closing_notes" rows="2" maxlength="1000"
                                    placeholder="Ej.: faltan $2.000 de una vuelta" />

                        <x-button type="submit" class="w-full">Cerrar caja</x-button>
                    </form>
                </x-card>
            @endunless

            {{-- Comisiones por pagar --}}
            <x-card title="Comisiones por pagar" :description="$startDate ? 'Desde el ' . $startDate->format('d/m/Y') . ', cuando se empezó a usar la caja' : null" flush>
                @if($balances->isNotEmpty())
                    <ul class="divide-y divide-border">
                        @foreach($balances as $row)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm">{{ $row['mechanic']->name }}</p>
                                    <p class="text-xs text-muted-foreground">Ganó {{ money($row['earned']) }} · pagado {{ money($row['paid']) }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="text-sm font-medium tabular-nums">{{ money($row['balance']) }}</span>
                                    @if(! $isClosed && $row['balance'] >= 1 && $row['mechanic']->is_active)
                                        <x-button variant="secondary" size="sm"
                                                  x-data
                                                  x-on:click="$dispatch('cash-pay-mechanic', { id: {{ $row['mechanic']->id }}, amount: '{{ ltrim(money($row['balance']), '$') }}' })">
                                            Pagar
                                        </x-button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="p-5">
                        <x-empty-state icon="users">
                            {{ $startDate ? 'No hay comisiones pendientes.' : 'Se empiezan a contar desde el primer día que se use la caja.' }}
                        </x-empty-state>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</div>
</x-app-layout>
