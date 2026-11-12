@php
    $monthLabel = ucfirst($month->translatedFormat('F Y'));
    $daily = $report['daily'];
    $maxDaily = max(1, (float) $daily->max('total'));
    $maxEarnings = max(1, (float) $report['mechanics']->max('earnings'));
    $maxProductQty = max(1, (int) $report['top_products']->max('quantity'));
    $salesTotal = max(1, (float) $report['income']['sales']);
@endphp

<x-app-layout>
<div class="ds">
    <x-page-header title="Reportes" :subtitle="$monthLabel . ($currentBranch ? ' · ' . $currentBranch->name : '')">
        <x-slot name="actions">
            <form method="GET" action="{{ route('reports') }}">
                <label for="month" class="sr-only">Mes</label>
                <select id="month" name="month" onchange="this.form.submit()"
                        class="h-10 cursor-pointer rounded-md border border-input bg-card pl-3 pr-8 text-sm text-foreground shadow-xs focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                    @foreach($months as $option)
                        <option value="{{ $option->format('Y-m') }}" @selected($option->format('Y-m') === $month->format('Y-m'))>
                            {{ ucfirst($option->translatedFormat('F Y')) }}
                        </option>
                    @endforeach
                </select>
            </form>
        </x-slot>
    </x-page-header>

    {{-- Indicadores del mes --}}
    @php
        $kpis = [
            ['label' => 'Ingresos del mes', 'value' => money($report['income']['total']), 'hint' => 'Órdenes ' . money($report['income']['orders']) . ' · Ventas ' . money($report['income']['sales'])],
            ['label' => 'Ganancia estimada', 'value' => money($report['profit']['total']), 'hint' => 'Mano de obra del taller ' . money($report['profit']['labor']) . ' · Margen de repuestos ' . money($report['profit']['parts_margin'])],
            ['label' => 'Órdenes entregadas', 'value' => $report['orders']['delivered'], 'hint' => $report['orders']['received'] . ' recibidas · ' . $report['sales_count'] . ' ventas de mostrador'],
            ['label' => 'Comisiones de mecánicos', 'value' => money($report['commissions']), 'hint' => 'Por trabajos completados en el mes'],
        ];
    @endphp
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                <p class="text-sm font-medium text-muted-foreground">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ $kpi['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Ingresos por día: una sola serie, un color; tooltip por barra y tabla para lectores de pantalla. --}}
        <x-card title="Ingresos por día" :description="'Máximo del mes: ' . money($daily->max('total') ?? 0)" class="lg:col-span-2 !overflow-visible">
            @if($daily->sum('total') > 0)
                <div class="relative h-56">
                    <div class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-border"></div>
                    <div class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-dashed border-border"></div>

                    <div class="relative flex h-full items-end gap-0.5 border-b border-border">
                        @foreach($daily as $index => $day)
                            @php
                                $height = $day['total'] > 0 ? max(2, round($day['total'] / $maxDaily * 100, 1)) : 0;
                                $align = $index < 4 ? 'left-0' : ($index > $daily->count() - 5 ? 'right-0' : 'left-1/2 -translate-x-1/2');
                            @endphp
                            <div class="group relative flex h-full flex-1 items-end" tabindex="0"
                                 aria-label="{{ $day['date']->translatedFormat('j \d\e F') }}: {{ money($day['total']) }}">
                                <div class="w-full rounded-t bg-chart-1 transition-opacity group-hover:opacity-80 group-focus:opacity-80" style="height: {{ $height }}%"></div>

                                <div class="pointer-events-none absolute bottom-full z-10 mb-2 hidden w-48 rounded-md border border-border bg-card px-3 py-2 text-xs text-foreground shadow-lg group-hover:block group-focus:block {{ $align }}">
                                    <p class="font-medium">{{ ucfirst($day['date']->translatedFormat('l j \d\e F')) }}</p>
                                    <p class="mt-1 flex justify-between gap-3"><span class="text-muted-foreground">Ventas</span><span class="tabular-nums">{{ money($day['sales']) }}</span></p>
                                    <p class="flex justify-between gap-3"><span class="text-muted-foreground">Órdenes</span><span class="tabular-nums">{{ money($day['orders']) }}</span></p>
                                    <p class="mt-1 flex justify-between gap-3 border-t border-border pt-1 font-medium"><span>Total</span><span class="tabular-nums">{{ money($day['total']) }}</span></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-2 flex gap-0.5" aria-hidden="true">
                    @foreach($daily as $day)
                        <span class="flex-1 text-center text-xs tabular-nums text-muted-foreground">
                            {{ $day['date']->day === 1 || $day['date']->day % 5 === 0 ? $day['date']->day : '' }}
                        </span>
                    @endforeach
                </div>

                {{-- Tabla para lectores de pantalla (un <table> no respeta el alto de sr-only: va dentro de un div) --}}
                <div class="sr-only">
                    <table>
                        <caption>Ingresos por día de {{ $monthLabel }}</caption>
                        <thead><tr><th>Día</th><th>Ventas</th><th>Órdenes</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach($daily as $day)
                                <tr><td>{{ $day['date']->format('d/m/Y') }}</td><td>{{ money($day['sales']) }}</td><td>{{ money($day['orders']) }}</td><td>{{ money($day['total']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state icon="bar-chart-3" class="h-56">Sin ingresos registrados en {{ $monthLabel }}.</x-empty-state>
            @endif
        </x-card>

        {{-- Comisiones por mecánico --}}
        <x-card title="Comisiones por mecánico">
            @if($report['mechanics']->isNotEmpty())
                <ul class="space-y-4">
                    @foreach($report['mechanics'] as $mech)
                        <li>
                            <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate">
                                    {{ $mech['name'] }}
                                    @unless($mech['is_active'])<span class="text-xs text-muted-foreground">(inactivo)</span>@endunless
                                </span>
                                <span class="shrink-0 font-medium tabular-nums">{{ money($mech['earnings']) }}</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-chart-1" style="width: {{ round($mech['earnings'] / $maxEarnings * 100) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $mech['jobs'] }} {{ $mech['jobs'] === 1 ? 'trabajo completado' : 'trabajos completados' }}</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="users">No hay mecánicos en esta sucursal.</x-empty-state>
            @endif
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Repuestos más vendidos --}}
        <x-card title="Repuestos más vendidos">
            @if($report['top_products']->isNotEmpty())
                <ul class="space-y-4">
                    @foreach($report['top_products'] as $product)
                        <li>
                            <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate">{{ $product['name'] }}</span>
                                <span class="shrink-0 font-medium tabular-nums">{{ $product['quantity'] }} und.</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-chart-1" style="width: {{ round($product['quantity'] / $maxProductQty * 100) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ money($product['revenue']) }} en ventas y órdenes</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="package">Sin ventas de repuestos en {{ $monthLabel }}.</x-empty-state>
            @endif
        </x-card>

        {{-- Ventas de mostrador por método de pago --}}
        <x-card title="Ventas por método de pago" flush>
            @if($report['payment_methods']->isNotEmpty())
                <ul class="divide-y divide-border">
                    @foreach($report['payment_methods'] as $method)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div>
                                <p class="text-sm">{{ $method->payment_method }}</p>
                                <p class="text-xs text-muted-foreground">{{ $method->sales }} {{ $method->sales == 1 ? 'venta' : 'ventas' }} · {{ round($method->total / $salesTotal * 100) }} %</p>
                            </div>
                            <span class="text-sm font-medium tabular-nums">{{ money($method->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-5">
                    <x-empty-state icon="wallet">Sin ventas de mostrador en {{ $monthLabel }}.</x-empty-state>
                </div>
            @endif
        </x-card>

        {{-- Estado del inventario (actual, no depende del mes) --}}
        <x-card title="Inventario actual" description="No depende del mes elegido">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Productos</dt>
                    <dd class="font-medium tabular-nums">{{ $report['inventory']['products'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Con stock bajo</dt>
                    <dd>
                        <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="font-medium tabular-nums {{ $report['inventory']['low_stock'] > 0 ? 'text-warning-text' : '' }} hover:underline">{{ $report['inventory']['low_stock'] }}</a>
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-border pt-3">
                    <dt class="text-muted-foreground">Valor al costo (lotes)</dt>
                    <dd class="font-medium tabular-nums">{{ money($report['inventory']['cost_value']) }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="font-medium">Valor de venta</dt>
                    <dd class="text-lg font-semibold tabular-nums">{{ money($report['inventory']['sale_value']) }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
</x-app-layout>
