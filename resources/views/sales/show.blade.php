<x-app-layout>
<div class="ds">
    <x-page-header :title="'Venta ' . $sale->displayNumber()"
                   :subtitle="'Registrada el ' . $sale->created_at->format('d/m/Y h:i a')"
                   :back="route('sales.index')" back-label="Ventas">
        <x-slot name="badge">
            <x-status-badge :tone="$sale->statusTone()">{{ $sale->statusLabel() }}</x-status-badge>
        </x-slot>
        <x-slot name="actions">
            @if(! $sale->isCancelled() && auth()->user()->can('admin'))
                <form action="{{ route('sales.cancel', $sale) }}" method="POST" class="no-print"
                      data-confirm="¿Anular la venta {{ $sale->displayNumber() }}?"
                      data-confirm-description="Los productos vuelven al inventario. La venta queda registrada como anulada."
                      data-confirm-label="Anular venta"
                      @include('cash.partials.refund-question', ['document' => $sale, 'amount' => $sale->total_amount])
                      data-confirm-destructive>
                    @csrf
                    <x-button type="submit" variant="secondary" class="!text-destructive-text">Anular</x-button>
                </form>
            @endif
            <x-button variant="secondary" href="{{ route('documents.sale', ['sale' => $sale, 'auto' => 1]) }}" target="_blank">
                <i data-lucide="printer" class="size-4"></i>
                Imprimir
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Productos" flush>
                <x-table :headers="['Producto', ['label' => 'Cant.', 'align' => 'right'], ['label' => 'Precio unit.', 'align' => 'right'], ['label' => 'Subtotal', 'align' => 'right']]">
                    @foreach($sale->saleProducts as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-muted-foreground">{{ money($item->unit_price) }}</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums">{{ money($item->total_price) }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>

            <x-card class="ml-auto max-w-sm">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-sm text-muted-foreground">{{ $sale->isCancelled() ? 'Total (anulada)' : 'Total' }}</span>
                    <span @class(['text-2xl font-semibold tabular-nums', 'text-muted-foreground line-through' => $sale->isCancelled()])>{{ money($sale->total_amount) }}</span>
                </div>
            </x-card>
        </div>

        <x-card title="Datos de la venta">
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Cliente</dt>
                    <dd class="mt-0.5">{{ $sale->customer_name ?: 'Venta mostrador' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Método de pago</dt>
                    <dd class="mt-0.5">{{ $sale->payment_method }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Fecha contable</dt>
                    <dd class="mt-0.5">{{ $sale->sale_date->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Registrada por</dt>
                    <dd class="mt-0.5">{{ $sale->user?->name ?? 'Sistema' }}</dd>
                </div>
            </dl>
            <p class="no-print mt-5 border-t border-border pt-4 text-xs text-muted-foreground">
                El stock se descontó de los lotes más antiguos.
                @can('admin')
                    <a href="{{ route('inventory.index') }}" class="font-medium text-primary hover:underline">Ver trazabilidad</a>
                @endcan
            </p>
        </x-card>
    </div>
</div>
</x-app-layout>

