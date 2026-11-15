<x-app-layout>
<div class="ds">
    <x-page-header title="Facturas" subtitle="Se generan desde el detalle de una orden con todos sus trabajos completados" />

    <x-card flush>
        @if($invoices->isNotEmpty())
            <x-table :headers="['Factura', 'Fecha y pago', 'Orden y cliente', 'Estado', ['label' => 'Total', 'align' => 'right'], ['label' => '', 'align' => 'right']]">
                @foreach($invoices as $invoice)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('invoices.show', $invoice) }}" class="font-mono font-medium text-primary hover:underline">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                            <p class="tabular-nums">{{ $invoice->invoice_date->format('d/m/Y') }}</p>
                            <p class="mt-0.5 text-xs">{{ $invoice->paymentMethodLabel() }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>
                                <a href="{{ route('service-orders.show', $invoice->service_order_id) }}" class="font-mono hover:underline">{{ $invoice->serviceOrder?->displayNumber() }}</a>
                                · {{ $invoice->serviceOrder?->customer_name }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $invoice->serviceOrder?->vehicle_info }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :tone="$invoice->statusTone()"
                                            :title="$invoice->isCancelled() ? 'Anulada el ' . $invoice->cancelled_at->format('d/m/Y h:i a') . ($invoice->cancelledBy ? ' por ' . $invoice->cancelledBy->name : '') : null">
                                {{ $invoice->statusLabel() }}
                            </x-status-badge>
                        </td>
                        <td @class(['whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums', 'text-muted-foreground line-through' => $invoice->isCancelled()])>
                            {{ money($invoice->amount) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                            <x-button variant="ghost" size="icon" href="{{ route('documents.invoice', ['invoice' => $invoice, 'auto' => 1]) }}" target="_blank" title="Imprimir comprobante">
                                <i data-lucide="printer" class="size-4"></i>
                                <span class="sr-only">Imprimir {{ $invoice->invoice_number }}</span>
                            </x-button>
                            @if(! $invoice->isCancelled() && auth()->user()->can('admin'))
                                <form action="{{ route('invoices.cancel', $invoice) }}" method="POST"
                                      data-confirm="¿Anular la factura {{ $invoice->invoice_number }}?"
                                      data-confirm-description="Queda registrada como anulada. La orden vuelve a «En progreso» para corregirla y facturarla de nuevo con el siguiente número."
                                      data-confirm-label="Anular factura"
                                      @include('cash.partials.refund-question', ['document' => $invoice, 'amount' => $invoice->amount])
                                      data-confirm-destructive>
                                    @csrf
                                    <x-button type="submit" variant="ghost" size="icon" class="hover:!text-destructive-text" title="Anular factura">
                                        <i data-lucide="ban" class="size-4"></i>
                                        <span class="sr-only">Anular la factura {{ $invoice->invoice_number }}</span>
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
                <x-empty-state icon="file-text">Aún no hay facturas. Se generan desde el detalle de una orden con todos sus trabajos completados.</x-empty-state>
            </div>
        @endif

        @if($invoices->hasPages())
            <x-slot name="footer">{{ $invoices->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
