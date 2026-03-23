<x-app-layout>
    <x-page-header title="Detalle de Venta" subtitle="Información detallada de la venta #S{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('sales.index') }}'">
                <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                Volver
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <x-card>
                <div class="space-y-6">
                    <div class="flex justify-between items-start border-b border-slate-100 pb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Resumen de Venta</h3>
                            <p class="text-sm text-slate-500">Registrado el {{ $sale->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($sale->status === 'completada')
                            <x-badge variant="emerald">Completada</x-badge>
                        @else
                            <x-badge variant="red">Anulada</x-badge>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Cliente</p>
                            <p class="font-bold text-slate-900">{{ $sale->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Fecha Contable</p>
                            <p class="font-bold text-slate-900">{{ $sale->sale_date->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4">Productos Vendidos</h4>
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 font-bold text-slate-700">Producto</th>
                                        <th class="px-4 py-3 font-bold text-slate-700 text-center">Cantidad</th>
                                        <th class="px-4 py-3 font-bold text-slate-700 text-right">P. Unitario</th>
                                        <th class="px-4 py-3 font-bold text-slate-700 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($sale->saleProducts as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-900 font-medium">{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                                        <td class="px-4 py-3 text-slate-600 text-center">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-slate-600 text-right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-slate-900 font-bold text-right">${{ number_format($item->total_price, 2, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-slate-100 pt-6">
                        <div class="flex justify-between items-center bg-slate-50 p-6 rounded-2xl">
                            <span class="text-sm font-bold text-slate-500 uppercase">Monto Total Recaudado</span>
                            <span class="text-3xl font-black text-slate-900">${{ number_format($sale->total_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

            </x-card>
        </div>

        <div>
            <x-card class="bg-blue-600 border-none text-white">
                <div class="space-y-4">
                    <div class="p-3 bg-white/20 rounded-xl w-fit">
                        <i data-lucide="info" class="h-6 w-6"></i>
                    </div>
                    <h3 class="text-lg font-bold">Información de Stock</h3>
                    <p class="text-sm text-blue-100 leading-relaxed">
                        Esta venta fue procesada y descontada automáticamente del inventario. Puedes ver el movimiento detallado en el módulo de **Trazabilidad**.
                    </p>
                    <x-button variant="secondary" class="w-full bg-white text-blue-600 border-none hover:bg-blue-50" onclick="window.location.href='{{ route('inventory.index') }}'">
                        Ver Trazabilidad
                    </x-button>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>

@if(request('print') == '1')
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif
