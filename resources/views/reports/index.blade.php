@php
    $money = fn ($value) => '$' . number_format((float) $value, 0, ',', '.');
    $monthLabel = ucfirst($month->translatedFormat('F Y'));
    $daily = $report['daily'];
    $maxDaily = max(1, (float) $daily->max('total'));
    $maxEarnings = max(1, (float) $report['mechanics']->max('earnings'));
    $maxProductQty = max(1, (int) $report['top_products']->max('quantity'));
    $salesTotal = max(1, (float) $report['income']['sales']);
@endphp

<x-app-layout>
    <x-page-header title="Reportes" subtitle="Resumen de {{ $monthLabel }}{{ $currentBranch ? ' · ' . $currentBranch->name : '' }}">
        <x-slot name="actions">
            <form method="GET" action="{{ route('reports') }}">
                <label for="month" class="sr-only">Mes</label>
                <select id="month" name="month" onchange="this.form.submit()"
                        class="rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 cursor-pointer dark:bg-gray-900 dark:border-gray-800 dark:text-gray-200">
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
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <x-card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Ingresos del mes</span>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg dark:bg-emerald-500/10">
                    <i data-lucide="dollar-sign" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $money($report['income']['total']) }}</p>
            <p class="mt-1 text-xs text-slate-500 font-medium">Órdenes {{ $money($report['income']['orders']) }} · Ventas {{ $money($report['income']['sales']) }}</p>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Ganancia estimada</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg dark:bg-blue-500/10">
                    <i data-lucide="trending-up" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600">{{ $money($report['profit']['total']) }}</p>
            <p class="mt-1 text-xs text-slate-500 font-medium">Mano de obra del taller {{ $money($report['profit']['labor']) }} · Margen repuestos {{ $money($report['profit']['parts_margin']) }}</p>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Órdenes</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg dark:bg-blue-500/10">
                    <i data-lucide="wrench" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $report['orders']['delivered'] }} <span class="text-sm font-bold text-slate-400">entregadas</span></p>
            <p class="mt-1 text-xs text-slate-500 font-medium">{{ $report['orders']['received'] }} recibidas · {{ $report['sales_count'] }} ventas de mostrador</p>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Comisiones mecánicos</span>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg dark:bg-amber-500/10">
                    <i data-lucide="users" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-amber-600">{{ $money($report['commissions']) }}</p>
            <p class="mt-1 text-xs text-slate-500 font-medium">Por trabajos completados en el mes</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
        {{-- Ingresos por día --}}
        <x-card class="lg:col-span-2">
            <x-slot name="header">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="bar-chart-3" class="h-5 w-5 text-blue-600"></i>
                        <h3 class="font-bold text-slate-900 dark:text-white">Ingresos por día</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-500">Máximo: {{ $money($daily->max('total') ?? 0) }}</span>
                </div>
            </x-slot>

            @if($daily->sum('total') > 0)
                <div class="relative h-56">
                    {{-- Líneas guía recesivas --}}
                    <div class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-slate-100 dark:border-gray-800"></div>
                    <div class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-dashed border-slate-100 dark:border-gray-800"></div>

                    <div class="relative flex h-full items-end gap-0.5 border-b border-slate-200 dark:border-gray-700">
                        @foreach($daily as $index => $day)
                            @php
                                $height = $day['total'] > 0 ? max(2, round($day['total'] / $maxDaily * 100, 1)) : 0;
                                $align = $index < 4 ? 'left-0' : ($index > $daily->count() - 5 ? 'right-0' : 'left-1/2 -translate-x-1/2');
                            @endphp
                            <div class="group relative flex h-full flex-1 items-end"
                                 tabindex="0"
                                 aria-label="{{ $day['date']->translatedFormat('j \d\e F') }}: {{ $money($day['total']) }}">
                                <div class="w-full rounded-t bg-brand-500 transition-opacity group-hover:opacity-80 group-focus:opacity-80" style="height: {{ $height }}%"></div>

                                <div class="pointer-events-none absolute bottom-full z-10 mb-2 hidden w-44 rounded-xl bg-slate-900 px-3 py-2 text-xs text-white shadow-lg group-hover:block group-focus:block {{ $align }} dark:bg-gray-700">
                                    <p class="font-bold">{{ ucfirst($day['date']->translatedFormat('l j \d\e F')) }}</p>
                                    <p class="mt-1 flex justify-between"><span class="text-slate-300">Ventas</span><span>{{ $money($day['sales']) }}</span></p>
                                    <p class="flex justify-between"><span class="text-slate-300">Órdenes</span><span>{{ $money($day['orders']) }}</span></p>
                                    <p class="mt-1 flex justify-between border-t border-white/20 pt-1 font-bold"><span>Total</span><span>{{ $money($day['total']) }}</span></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-2 flex gap-0.5">
                    @foreach($daily as $day)
                        <span class="flex-1 text-center text-[10px] font-bold text-slate-400">
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
                                <tr><td>{{ $day['date']->format('d/m/Y') }}</td><td>{{ $money($day['sales']) }}</td><td>{{ $money($day['orders']) }}</td><td>{{ $money($day['total']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex h-56 items-center justify-center">
                    <p class="text-slate-400 font-medium">Sin ingresos registrados en {{ $monthLabel }}.</p>
                </div>
            @endif
        </x-card>

        {{-- Comisiones por mecánico --}}
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900 dark:text-white">Comisiones por mecánico</h3>
                </div>
            </x-slot>
            <div class="space-y-5">
                @forelse($report['mechanics'] as $index => $mech)
                    <div class="flex items-center gap-4">
                        <div class="h-8 w-8 shrink-0 rounded-full bg-blue-50 flex items-center justify-center text-xs font-bold text-blue-600 dark:bg-blue-500/10">
                            {{ $index + 1 }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex justify-between gap-2 mb-1">
                                <span class="truncate text-sm font-bold text-slate-700 dark:text-gray-200">
                                    {{ $mech['name'] }}@unless($mech['is_active'])<span class="ml-1 text-[10px] font-bold uppercase text-slate-400">(inactivo)</span>@endunless
                                </span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $money($mech['earnings']) }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 dark:bg-gray-800">
                                <div class="bg-brand-500 h-1.5 rounded-full" style="width: {{ round($mech['earnings'] / $maxEarnings * 100) }}%"></div>
                            </div>
                            <p class="mt-1 text-[10px] font-bold text-slate-400">{{ $mech['jobs'] }} trabajo(s) completado(s)</p>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-slate-400 font-medium">No hay mecánicos en esta sucursal.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        {{-- Repuestos más vendidos --}}
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="package" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900 dark:text-white">Repuestos más vendidos</h3>
                </div>
            </x-slot>
            <div class="space-y-4">
                @forelse($report['top_products'] as $product)
                    <div>
                        <div class="flex justify-between gap-2 mb-1">
                            <span class="truncate text-sm font-bold text-slate-700 dark:text-gray-200">{{ $product['name'] }}</span>
                            <span class="shrink-0 text-sm font-bold text-slate-900 dark:text-white">{{ $product['quantity'] }} und.</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 dark:bg-gray-800">
                            <div class="bg-brand-500 h-1.5 rounded-full" style="width: {{ round($product['quantity'] / $maxProductQty * 100) }}%"></div>
                        </div>
                        <p class="mt-1 text-[10px] font-bold text-slate-400">{{ $money($product['revenue']) }} en ventas y órdenes</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-slate-400 font-medium">Sin ventas de repuestos en {{ $monthLabel }}.</p>
                @endforelse
            </div>
        </x-card>

        {{-- Ventas de mostrador por método de pago --}}
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="wallet" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900 dark:text-white">Ventas por método de pago</h3>
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse($report['payment_methods'] as $method)
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-white/[0.03]">
                        <div>
                            <p class="text-sm font-bold text-slate-700 dark:text-gray-200">{{ $method->payment_method }}</p>
                            <p class="text-[10px] font-bold text-slate-400">{{ $method->sales }} venta(s) · {{ round($method->total / $salesTotal * 100) }}%</p>
                        </div>
                        <span class="text-sm font-black text-slate-900 dark:text-white">{{ $money($method->total) }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-slate-400 font-medium">Sin ventas de mostrador en {{ $monthLabel }}.</p>
                @endforelse
            </div>
        </x-card>

        {{-- Estado del inventario (actual) --}}
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="archive" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900 dark:text-white">Inventario actual</h3>
                </div>
            </x-slot>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 rounded-2xl bg-slate-50 text-center dark:bg-white/[0.03]">
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $report['inventory']['products'] }}</p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Productos</p>
                </div>
                <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="p-4 rounded-2xl bg-amber-50 text-center hover:bg-amber-100 transition-colors dark:bg-amber-500/10">
                    <p class="text-3xl font-black text-amber-600">{{ $report['inventory']['low_stock'] }}</p>
                    <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Stock bajo</p>
                </a>
            </div>
            <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-gray-800">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-slate-700 dark:text-gray-300">Valor al costo (lotes):</span>
                    <span class="text-sm font-black text-slate-900 dark:text-white">{{ $money($report['inventory']['cost_value']) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl dark:bg-emerald-500/10">
                    <span class="text-sm font-bold text-emerald-800 dark:text-emerald-300">Valor de venta:</span>
                    <span class="text-lg font-black text-emerald-600">{{ $money($report['inventory']['sale_value']) }}</span>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
