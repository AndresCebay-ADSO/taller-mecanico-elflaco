<x-app-layout>
<div class="ds">
    <x-page-header title="Dashboard" subtitle="Vista general del taller">
        <x-slot name="actions">
            <x-button variant="secondary" href="{{ route('inventory.purchase') }}">Registrar compra</x-button>
            <x-button variant="secondary" href="{{ route('service-orders.create') }}">Nueva orden</x-button>
            <x-button href="{{ route('sales.create') }}">Nueva venta</x-button>
        </x-slot>
    </x-page-header>

    @if($backupStatus?->needsAttention())
        <div class="mb-4 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ \App\Support\Tone::alert('danger') }}" role="alert">
            <i data-lucide="database-backup" class="mt-0.5 size-4 shrink-0"></i>
            <p><span class="font-medium">Los datos no se están respaldando.</span> {{ $backupStatus->describe() }}
                Revisa que el portátil tenga Google Drive abierto o ejecuta <span class="font-mono">respaldo.bat</span>.</p>
        </div>
    @endif

    @if($lowStockCount > 0)
        <div class="mb-6 flex flex-col gap-2 rounded-lg border px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between {{ \App\Support\Tone::alert('warning') }}">
            <p class="flex items-center gap-2 font-medium">
                <i data-lucide="alert-triangle" class="size-4 shrink-0"></i>
                {{ $lowStockCount }} {{ $lowStockCount === 1 ? 'producto tiene' : 'productos tienen' }} stock bajo y {{ $lowStockCount === 1 ? 'necesita' : 'necesitan' }} reposición.
            </p>
            <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="font-medium underline-offset-4 hover:underline">Ver inventario</a>
        </div>
    @endif

    {{-- Indicadores --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $kpis = [
                ['label' => 'Ingresos de hoy', 'value' => money($todayEarnings), 'hint' => 'Ventas + órdenes entregadas hoy', 'icon' => 'wallet'],
                ['label' => 'Ingresos del mes', 'value' => money($monthEarnings), 'hint' => ucfirst(now()->translatedFormat('F Y')), 'icon' => 'trending-up', 'link' => route('reports')],
                ['label' => 'Órdenes activas', 'value' => $activeOrders, 'hint' => $pendingOrders . ' pendientes de entrega', 'icon' => 'clipboard-list', 'link' => route('service-orders.index')],
                ['label' => 'Productos', 'value' => $totalProducts, 'hint' => $lowStockCount . ' con stock bajo', 'icon' => 'package', 'link' => route('products.index')],
            ];
        @endphp
        @foreach($kpis as $kpi)
            <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted-foreground">{{ $kpi['label'] }}</p>
                    <i data-lucide="{{ $kpi['icon'] }}" class="size-4 text-muted-foreground"></i>
                </div>
                <p class="mt-2 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    @isset($kpi['link'])
                        <a href="{{ $kpi['link'] }}" class="hover:text-foreground hover:underline">{{ $kpi['hint'] }}</a>
                    @else
                        {{ $kpi['hint'] }}
                    @endisset
                </p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Últimas ventas --}}
        <x-card title="Últimas ventas" flush>
            <x-slot name="actions">
                <a href="{{ route('sales.index') }}" class="text-sm font-medium text-primary hover:underline">Ver todas</a>
            </x-slot>

            @if($recentSales->isNotEmpty())
                <ul class="divide-y divide-border">
                    @foreach($recentSales as $sale)
                        @php
                            $productsSummary = $sale->saleProducts
                                ->take(2)
                                ->map(fn ($sp) => $sp->quantity . '× ' . ($sp->product->name ?? 'Producto eliminado'))
                                ->implode(', ') . ($sale->saleProducts->count() > 2 ? '…' : '');
                        @endphp
                        <li>
                            <a href="{{ route('sales.show', $sale) }}" class="flex items-center justify-between gap-4 px-5 py-3 transition-colors hover:bg-muted/50">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium" title="{{ $sale->customer_name ?: 'Venta mostrador' }}">{{ $sale->customer_name ?: 'Venta mostrador' }}</p>
                                    <p class="mt-0.5 truncate text-xs text-muted-foreground" title="{{ $productsSummary }}">{{ $productsSummary }} · {{ $sale->created_at->format('h:i a') }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <x-status-badge>{{ $sale->payment_method }}</x-status-badge>
                                    <span class="w-24 text-right text-sm font-medium tabular-nums">{{ money($sale->total_amount) }}</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-5">
                    <x-empty-state icon="shopping-bag">Aún no hay ventas.</x-empty-state>
                </div>
            @endif
        </x-card>

        {{-- Stock bajo --}}
        <x-card title="Stock bajo">
            <x-slot name="actions">
                <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="text-sm font-medium text-primary hover:underline">Ver todo</a>
            </x-slot>

            @if($lowStockProducts->isNotEmpty())
                <ul class="space-y-4">
                    @foreach($lowStockProducts->take(6) as $product)
                        @php
                            $percent = $product->min_stock > 0 ? min(100, round(($product->stock / $product->min_stock) * 100)) : 0;
                            // Todos están en o bajo el mínimo: rojo si está agotado o queda un tercio o menos.
                            $critical = $product->stock <= 0 || $percent <= 33;
                        @endphp
                        <li>
                            <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                <a href="{{ route('products.show', $product) }}" title="{{ $product->name }}" class="truncate text-sm font-medium hover:underline">{{ $product->name }}</a>
                                <span class="shrink-0 text-xs tabular-nums {{ \App\Support\Tone::text($critical ? 'danger' : 'warning') }}">
                                    {{ $product->stock }} / {{ $product->min_stock }} mín.
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full {{ $critical ? 'bg-destructive' : 'bg-warning' }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="check-circle">Ningún producto está bajo el mínimo.</x-empty-state>
            @endif
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Comisiones --}}
        <x-card title="Comisiones del mes" :description="ucfirst(now()->translatedFormat('F Y'))" class="lg:col-span-2" flush>
            @if($mechanics->isNotEmpty())
                <x-table :headers="['Mecánico', ['label' => 'Trabajos', 'align' => 'right'], ['label' => 'Comisión', 'align' => 'right']]">
                    @foreach($mechanics as $mech)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $mech->name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-muted-foreground">{{ $mech->total_jobs }}</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums">{{ money($mech->monthly_earnings) }}</td>
                        </tr>
                    @endforeach
                </x-table>
            @else
                <div class="p-5">
                    <x-empty-state icon="users">No hay mecánicos activos.</x-empty-state>
                </div>
            @endif
        </x-card>

        {{-- Lo cobrado hoy (mostrador + servicios) por método de pago --}}
        <x-card title="Cobrado hoy por método" description="Ventas de mostrador y comprobantes de servicio">
            @if($todayByMethod->isNotEmpty())
                <dl class="space-y-3">
                    @foreach($todayByMethod as $method)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <dt class="text-muted-foreground">{{ $method->payment_method }}</dt>
                            <dd class="font-medium tabular-nums">{{ money($method->total) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <x-empty-state>Hoy no se ha cobrado nada.</x-empty-state>
            @endif
        </x-card>
    </div>
</div>
</x-app-layout>
