@php
    $serviceOrders = \App\Models\ServiceOrder::all();
@endphp

<x-app-layout>
    <x-page-header title="Generar Factura" subtitle="Crea una nueva factura asociada a una orden de servicio.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('invoices.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('invoices.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Número de Factura" name="invoice_number" required placeholder="Ej. FAC-0001" />
                    <x-input label="Fecha de Factura" name="invoice_date" type="date" required value="{{ date('Y-m-d') }}" />
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Monto Total" name="amount" type="number" step="0.01" required placeholder="0.00" />
                    
                    <div class="space-y-1.5">
                        <label for="service_order_id" class="block text-sm font-semibold text-slate-700">Orden de Servicio <span class="text-red-500">*</span></label>
                        <select id="service_order_id" name="service_order_id" required class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                            <option value="">Seleccione una orden...</option>
                            @foreach($serviceOrders as $order)
                                <option value="{{ $order->id }}">Orden #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }} - {{ $order->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Generar Factura
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
