@php
    $order = $invoice->serviceOrder;
    $customer = $invoice->customer();
    $lines = $invoice->lineItems();
    $laborTotal = $lines->sum('labor');
    $partsTotal = $lines->sum(fn ($line) => collect($line['parts'])->sum('total_price'));
    $cancelled = $invoice->isCancelled()
        ? 'Anulado el ' . $invoice->cancelled_at->format('d/m/Y h:i a') . ($invoice->cancelledBy ? ' por ' . $invoice->cancelledBy->name : '') . '. No tiene validez como comprobante.'
        : null;
@endphp

<x-document title="Comprobante de servicio" :number="$invoice->invoice_number" :settings="$settings"
            :back="route('invoices.show', $invoice)" :cancelled="$cancelled">
    <x-slot name="parties">
        <x-document.party title="Cliente">
            <p class="font-medium">{{ $customer['customer_name'] }}</p>
            @if($customer['customer_document'])<p>C.C./NIT {{ $customer['customer_document'] }}</p>@endif
            @if($customer['customer_phone'])<p class="tabular-nums">Tel. {{ $customer['customer_phone'] }}</p>@endif
        </x-document.party>
        <x-document.party title="Vehículo">
            <p class="font-medium">{{ $customer['vehicle_info'] }}</p>
            <p class="text-muted-foreground">{{ \Illuminate\Support\Str::limit($customer['service_description'], 120) }}</p>
        </x-document.party>
    </x-slot>

    <x-slot name="meta">
        <x-document.meta label="Fecha">{{ $invoice->invoice_date->format('d/m/Y') }}</x-document.meta>
        <x-document.meta label="Hora">{{ $invoice->created_at->format('h:i a') }}</x-document.meta>
        <x-document.meta label="Orden" class="font-mono">{{ $order->displayNumber() }}</x-document.meta>
        @if($invoice->payment_method)
            <x-document.meta label="Pago">{{ $invoice->payment_method }}</x-document.meta>
        @endif
        @if($order->branch)
            <x-document.meta label="Sede">{{ $order->branch->name }}</x-document.meta>
        @endif
    </x-slot>

    <x-document.job-lines :lines="$lines" />

    <x-slot name="totals">
        <dl class="space-y-1.5">
            <div class="flex justify-between"><dt class="text-muted-foreground">Mano de obra</dt><dd class="tabular-nums">{{ money($laborTotal) }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted-foreground">Repuestos</dt><dd class="tabular-nums">{{ money($partsTotal) }}</dd></div>
            <div class="flex items-baseline justify-between border-t-2 border-primary pt-2">
                <dt class="text-base font-semibold uppercase text-primary">Total</dt>
                <dd class="text-2xl font-semibold tabular-nums text-primary">{{ money($invoice->amount) }}</dd>
            </div>
        </dl>
        <p class="mt-2 text-right text-xs text-muted-foreground">Son: {{ \App\Support\NumberToWords::pesos($invoice->amount) }}</p>
    </x-slot>

    <x-slot name="signatures">
        <x-document.signature label="Entregó" :hint="$settings['workshop_name'] ?? null" />
        <x-document.signature label="Recibí conforme" hint="Nombre y C.C." />
    </x-slot>

    <x-slot name="notes">
        <p class="text-base font-semibold text-primary">{{ $settings['footer_text_invoice'] ?? null ?: '¡Gracias por su confianza!' }}</p>
    </x-slot>

    @if(!empty($settings['document_conditions']))
        <x-slot name="conditions">{{ $settings['document_conditions'] }}</x-slot>
    @endif
</x-document>
