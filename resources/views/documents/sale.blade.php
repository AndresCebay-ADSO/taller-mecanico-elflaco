@php
    $cancelled = $sale->isCancelled() ? 'Venta anulada: los productos volvieron al inventario. No tiene validez como comprobante.' : null;
@endphp

<x-document title="Comprobante de venta" :number="$sale->displayNumber()" :settings="$settings"
            :back="route('sales.show', $sale)" :cancelled="$cancelled">
    <x-slot name="parties">
        <x-document.party title="Cliente">
            <p class="font-medium">{{ $sale->customer_name ?: 'Venta mostrador' }}</p>
        </x-document.party>
        <x-document.party title="Pago">
            <p class="font-medium">{{ $sale->payment_method }}</p>
            <p class="text-muted-foreground">Atendió: {{ $sale->user?->name ?? 'Sistema' }}</p>
        </x-document.party>
    </x-slot>

    <x-slot name="meta">
        <x-document.meta label="Fecha">{{ $sale->sale_date->format('d/m/Y') }}</x-document.meta>
        <x-document.meta label="Hora">{{ $sale->created_at->format('h:i a') }}</x-document.meta>
        @if($sale->branch)
            <x-document.meta label="Sede">{{ $sale->branch->name }}</x-document.meta>
        @endif
    </x-slot>

    <x-document.lines>
        @foreach($sale->saleProducts as $item)
            <tr>
                <td class="px-2 py-2.5 text-center tabular-nums">{{ $item->quantity }}</td>
                <td class="px-2 py-2.5">{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                <td class="px-2 py-2.5 text-right tabular-nums">{{ money($item->unit_price) }}</td>
                <td class="px-2 py-2.5 text-right tabular-nums">{{ money($item->total_price) }}</td>
            </tr>
        @endforeach
    </x-document.lines>

    <x-slot name="totals">
        <div class="flex items-baseline justify-between border-t-2 border-primary pt-2">
            <span class="text-base font-semibold uppercase text-primary">Total</span>
            <span class="text-2xl font-semibold tabular-nums text-primary">{{ money($sale->total_amount) }}</span>
        </div>
        <p class="mt-2 text-right text-xs text-muted-foreground">Son: {{ \App\Support\NumberToWords::pesos($sale->total_amount) }}</p>
    </x-slot>

    <x-slot name="notes">
        <p class="text-base font-semibold text-primary">{{ $settings['footer_text_invoice'] ?? null ?: '¡Gracias por su compra!' }}</p>
    </x-slot>

    @if(!empty($settings['document_conditions']))
        <x-slot name="conditions">{{ $settings['document_conditions'] }}</x-slot>
    @endif
</x-document>
