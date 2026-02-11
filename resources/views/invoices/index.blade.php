<x-app-layout>
    <x-page-header title="Facturación" subtitle="Historial de facturas generadas por el sistema.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('invoices.create') }}'">
                <i data-lucide="file-text" class="mr-2 h-4 w-4"></i>
                Generar Factura
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        <x-table :headers="['Número', 'Fecha', 'Monto', 'Estado']">
            @forelse($invoices as $invoice)
                <tr>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        {{ $invoice->invoice_number }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        ${{ number_format($invoice->amount, 2) }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <x-badge variant="blue">Emitida</x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-slate-600 hover:text-slate-900">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            <x-button variant="ghost" size="sm">
                                <i data-lucide="download" class="h-4 w-4"></i>
                            </x-button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        No hay facturas registradas.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </x-card>
</x-app-layout>
