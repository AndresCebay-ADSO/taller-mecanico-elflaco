<x-app-layout>
<div class="ds">
    <x-page-header title="Ventas directas" subtitle="Ventas de mostrador de repuestos y productos">
        <x-slot name="actions">
            <x-button href="{{ route('sales.create') }}">Nueva venta</x-button>
        </x-slot>
    </x-page-header>

    {{-- Resumen de hoy --}}
    <x-card class="mb-4">
        <div class="flex flex-col gap-6 md:flex-row md:items-start">
            <div class="md:w-56 md:shrink-0">
                <p class="text-sm font-medium text-muted-foreground">Ventas de hoy</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ money($todayTotal) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ $todayCount }} {{ $todayCount === 1 ? 'venta' : 'ventas' }}</p>
            </div>

            @if($todayByMethod->isNotEmpty())
                <div class="grid flex-1 grid-cols-1 gap-x-8 gap-y-4 border-t border-border pt-4 sm:grid-cols-2 md:border-l md:border-t-0 md:pl-6 md:pt-0 lg:grid-cols-3">
                    @foreach($todayByMethod as $method)
                        @php $percentage = $todayTotal > 0 ? round(($method->total / $todayTotal) * 100) : 0; @endphp
                        <div>
                            <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                <span class="text-muted-foreground">{{ $method->payment_method }}</span>
                                <span class="font-medium tabular-nums">{{ money($method->total) }}</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted" title="{{ $percentage }} % del total de hoy">
                                <div class="h-full rounded-full bg-foreground/30" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-card>

    <x-search-filter action="{{ route('sales.index') }}" searchPlaceholder="Cliente, producto o número de venta">
        <x-select label="Estado" name="status">
            <option value="">Todos</option>
            <option value="completada" @selected(request('status') === 'completada')>Completada</option>
            <option value="anulada" @selected(request('status') === 'anulada')>Anulada</option>
        </x-select>
        <x-select label="Método de pago" name="payment_method">
            <option value="">Todos</option>
            @foreach(\App\Support\PaymentMethod::ALL as $method)
                <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ $method }}</option>
            @endforeach
        </x-select>
        <x-input type="date" label="Fecha" name="date_start" :value="request('date_start')" />
    </x-search-filter>

    <x-card flush>
        @if($sales->isNotEmpty())
            <x-table :headers="['Venta', 'Cliente', 'Productos', 'Estado', ['label' => 'Total', 'align' => 'right'], ['label' => '', 'align' => 'right']]">
                @foreach($sales as $sale)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="whitespace-nowrap px-4 py-3 align-top">
                            <a href="{{ route('sales.show', $sale) }}" class="font-mono font-medium text-primary hover:underline">{{ $sale->displayNumber() }}</a>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $sale->created_at->format('d/m/Y h:i a') }}</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <p class="font-medium">{{ $sale->customer_name ?: 'Venta mostrador' }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $sale->payment_method }} · {{ $sale->user?->name ?? 'Sistema' }}</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <ul class="space-y-0.5">
                                @foreach($sale->saleProducts as $item)
                                    <li class="flex gap-1.5">
                                        <span class="max-w-56 truncate" title="{{ $item->product?->name }}">{{ $item->product?->name ?? 'Producto eliminado' }}</span>
                                        <span class="shrink-0 tabular-nums text-muted-foreground">× {{ $item->quantity }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <x-status-badge :tone="$sale->statusTone()">{{ $sale->statusLabel() }}</x-status-badge>
                        </td>
                        <td @class(['whitespace-nowrap px-4 py-3 text-right align-top font-medium tabular-nums', 'text-muted-foreground line-through' => $sale->isCancelled()])>
                            {{ money($sale->total_amount) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-right align-top">
                            <div class="flex items-center justify-end gap-1">
                                <x-button variant="ghost" size="icon" href="{{ route('documents.sale', ['sale' => $sale, 'auto' => 1]) }}" target="_blank" title="Imprimir comprobante">
                                    <i data-lucide="printer" class="size-4"></i>
                                    <span class="sr-only">Imprimir comprobante de la venta {{ $sale->displayNumber() }}</span>
                                </x-button>
                                @if(! $sale->isCancelled() && auth()->user()->can('admin'))
                                    <form action="{{ route('sales.cancel', $sale) }}" method="POST"
                                          data-confirm="¿Anular la venta {{ $sale->displayNumber() }}?"
                                          data-confirm-description="Los productos vuelven al inventario. La venta queda registrada como anulada."
                                          data-confirm-label="Anular venta"
                                          @include('cash.partials.refund-question', ['document' => $sale, 'amount' => $sale->total_amount])
                                          data-confirm-destructive>
                                        @csrf
                                        <x-button type="submit" variant="ghost" size="icon" class="hover:!text-destructive-text" title="Anular venta">
                                            <i data-lucide="ban" class="size-4"></i>
                                            <span class="sr-only">Anular la venta {{ $sale->displayNumber() }}</span>
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="shopping-cart">
                    {{ request()->except('page') ? 'Ninguna venta coincide con la búsqueda.' : 'Aún no hay ventas registradas.' }}
                    @unless(request()->except('page'))
                        <x-slot name="action"><x-button href="{{ route('sales.create') }}">Nueva venta</x-button></x-slot>
                    @endunless
                </x-empty-state>
            </div>
        @endif

        @if($sales->hasPages())
            <x-slot name="footer">{{ $sales->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
