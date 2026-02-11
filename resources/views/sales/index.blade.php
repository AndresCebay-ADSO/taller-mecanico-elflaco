<x-app-layout>
    <x-page-header title="Ventas" subtitle="Historial de ventas de repuestos y servicios.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('sales.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nueva Venta
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        <x-table :headers="['ID', 'Fecha', 'Total', 'Estado']">
            @forelse($sales as $sale)
                <tr>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        #S{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        ${{ number_format($sale->total_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <x-badge variant="emerald">Completada</x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('sales.show', $sale) }}" class="text-slate-600 hover:text-slate-900">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        No hay ventas registradas.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </x-card>
</x-app-layout>
