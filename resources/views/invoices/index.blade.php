<x-app-layout>
    <x-page-header title="Facturación" subtitle="Facturas generadas desde las órdenes de servicio de esta sucursal." />

    <x-card class="overflow-hidden p-0">
        <x-table :headers="['NÚMERO', 'FECHA', 'ORDEN / CLIENTE', 'MONTO', 'ESTADO', 'ACCIONES']">
            @forelse($invoices as $invoice)
                <tr>
                    <td class="px-6 py-4 font-mono text-sm font-bold text-slate-900 dark:text-white">
                        {{ $invoice->invoice_number }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                        {{ $invoice->invoice_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-slate-900 dark:text-white">Orden #{{ str_pad($invoice->service_order_id, 4, '0', STR_PAD_LEFT) }}</div>
                        <div class="text-xs text-slate-500">{{ $invoice->serviceOrder?->customer_name }} · {{ $invoice->serviceOrder?->vehicle_info }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-black {{ $invoice->isCancelled() ? 'text-slate-400 line-through' : 'text-slate-900 dark:text-white' }}">
                        ${{ number_format($invoice->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($invoice->isCancelled())
                            <x-badge variant="error" title="Anulada el {{ $invoice->cancelled_at->format('d/m/Y H:i') }}{{ $invoice->cancelledBy ? ' por ' . $invoice->cancelledBy->name : '' }}">Anulada</x-badge>
                        @else
                            <x-badge variant="success">Vigente</x-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('invoices.show', $invoice) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-blue-600 transition-colors" title="Ver factura">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            @if(!$invoice->isCancelled() && auth()->user()->can('admin'))
                                <form action="{{ route('invoices.cancel', $invoice) }}" method="POST" class="inline"
                                    onsubmit="return confirm('¿Anular la factura {{ $invoice->invoice_number }}? Quedará registrada como anulada y la orden podrá facturarse de nuevo.')">
                                    @csrf
                                    <button type="submit" class="rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer" title="Anular factura">
                                        <i data-lucide="ban" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        No hay facturas registradas. Se generan desde el detalle de una orden con todos sus trabajos completados.
                    </td>
                </tr>
            @endforelse
        </x-table>
        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-gray-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </x-card>
</x-app-layout>
