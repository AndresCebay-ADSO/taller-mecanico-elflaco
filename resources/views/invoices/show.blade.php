@php
    $order = $invoice->serviceOrder;
    $lines = $invoice->lineItems();
    $laborTotal = $lines->sum('labor');
    $partsTotal = $lines->sum(fn ($line) => collect($line['parts'])->sum('total_price'));
    $orderNumber = $order->displayNumber();
@endphp

<x-app-layout>
<div class="ds">
    <div class="no-print">
        <x-page-header :title="'Factura ' . $invoice->invoice_number"
                       :subtitle="'Orden ' . $orderNumber . ' · ' . $order->customer_name"
                       :back="route('invoices.index')" back-label="Facturas">
            <x-slot name="badge">
                <x-status-badge :tone="$invoice->statusTone()">{{ $invoice->statusLabel() }}</x-status-badge>
            </x-slot>
            <x-slot name="actions">
                <x-button variant="secondary" href="{{ route('service-orders.show', $order) }}">Ver orden</x-button>
                @if(! $invoice->isCancelled() && auth()->user()->can('admin'))
                    <form action="{{ route('invoices.cancel', $invoice) }}" method="POST"
                          data-confirm="¿Anular la factura {{ $invoice->invoice_number }}?"
                          data-confirm-description="Queda registrada como anulada. La orden vuelve a «En progreso» para corregirla y facturarla de nuevo con el siguiente número."
                          data-confirm-label="Anular factura"
                          @include('cash.partials.refund-question', ['document' => $invoice, 'amount' => $invoice->amount])
                          data-confirm-destructive>
                        @csrf
                        <x-button type="submit" variant="secondary" class="!text-destructive-text">Anular</x-button>
                    </form>
                @endif
                <x-button href="{{ route('documents.invoice', ['invoice' => $invoice, 'auto' => 1]) }}" target="_blank">
                    <i data-lucide="printer" class="size-4"></i>
                    Imprimir
                </x-button>
            </x-slot>
        </x-page-header>
    </div>

    @if($invoice->isCancelled())
        <div class="mx-auto mb-6 flex max-w-4xl items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ \App\Support\Tone::alert('danger') }}">
            <i data-lucide="ban" class="mt-0.5 size-4 shrink-0"></i>
            <div>
                <p class="font-semibold">Factura anulada</p>
                <p class="mt-0.5">
                    El {{ $invoice->cancelled_at->format('d/m/Y h:i a') }}{{ $invoice->cancelledBy ? ' por ' . $invoice->cancelledBy->name : '' }}. No tiene validez como comprobante.
                </p>
            </div>
        </div>
    @endif

    {{-- Vista en pantalla. Lo impreso sale de documents/invoice (botón «Imprimir»). --}}
    <article class="mx-auto max-w-4xl rounded-lg border border-border bg-card p-6 shadow-xs md:p-10">
        <header class="flex flex-col gap-6 border-b border-border pb-6 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ $settings['workshop_name'] ?? config('app.name') }}</h2>
                <div class="mt-2 space-y-0.5 text-sm text-muted-foreground">
                    @if(!empty($settings['workshop_nit']))
                        <p>NIT {{ $settings['workshop_nit'] }}</p>
                    @endif
                    @if($order->branch)
                        <p>{{ $order->branch->name }}{{ $order->branch->address ? ' · ' . $order->branch->address : '' }}</p>
                    @endif
                    @if(!empty($settings['workshop_phone']))
                        <p>Tel. {{ $settings['workshop_phone'] }}</p>
                    @endif
                    @if(!empty($settings['workshop_email']))
                        <p>{{ $settings['workshop_email'] }}</p>
                    @endif
                </div>
            </div>
            <div class="md:text-right">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Factura de servicio</p>
                <p class="mt-1 font-mono text-xl font-semibold">{{ $invoice->invoice_number }}</p>
                <dl class="mt-2 space-y-0.5 text-sm">
                    <div><dt class="inline text-muted-foreground">Fecha:</dt> <dd class="inline tabular-nums">{{ $invoice->invoice_date->format('d/m/Y') }}</dd></div>
                    <div><dt class="inline text-muted-foreground">Orden:</dt> <dd class="inline font-mono">{{ $orderNumber }}</dd></div>
                    <div><dt class="inline text-muted-foreground">Pago:</dt> <dd class="inline">{{ $invoice->paymentMethodLabel() }}</dd></div>
                </dl>
            </div>
        </header>

        <section class="grid grid-cols-1 gap-6 border-b border-border py-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-medium text-muted-foreground">Cliente</p>
                <p class="mt-1 font-medium">{{ $order->customer_name }}</p>
                <p class="text-sm tabular-nums text-muted-foreground">{{ $order->customer_phone ?: 'Sin teléfono' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-muted-foreground">Vehículo</p>
                <p class="mt-1 font-medium">{{ $order->vehicle_info }}</p>
                <p class="text-sm text-muted-foreground">{{ \Illuminate\Support\Str::limit($order->service_description, 140) }}</p>
            </div>
        </section>

        <div class="overflow-x-auto py-6">
            <x-document.job-lines :lines="$lines" />
        </div>

        <div class="flex justify-end border-t border-border pt-6">
            <dl class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-muted-foreground">Mano de obra</dt>
                    <dd class="tabular-nums">{{ money($laborTotal) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted-foreground">Repuestos</dt>
                    <dd class="tabular-nums">{{ money($partsTotal) }}</dd>
                </div>
                <div class="flex items-baseline justify-between border-t border-border pt-3">
                    <dt class="font-medium">Total</dt>
                    <dd @class(['text-2xl font-semibold tabular-nums', 'line-through' => $invoice->isCancelled()])>{{ money($invoice->amount) }}</dd>
                </div>
            </dl>
        </div>

        @if(!empty($settings['footer_text_invoice']))
            <p class="mt-8 border-t border-dashed border-border pt-6 text-center text-xs text-muted-foreground">
                {{ $settings['footer_text_invoice'] }}
            </p>
        @endif
    </article>
</div>
</x-app-layout>
