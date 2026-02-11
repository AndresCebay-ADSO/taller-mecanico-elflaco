<x-app-layout>
    <x-page-header title="Detalle de Factura {{ $invoice->invoice_number }}" subtitle="Información técnica y financiera de la factura.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('invoices.index') }}'">
                <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                Volver
            </x-button>
            <x-button variant="primary">
                <i data-lucide="download" class="mr-2 h-4 w-4"></i>
                Descargar PDF
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card>
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 leading-none">FACTURA</h2>
                        <p class="text-slate-500 mt-2 font-mono">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Fecha de Emisión</p>
                        <p class="text-lg font-bold text-slate-900">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8 mb-8 pt-8 border-t border-slate-100">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Asociado a Orden</p>
                        <p class="text-slate-900 font-bold text-lg">Orden #{{ str_pad($invoice->service_order_id, 4, '0', STR_PAD_LEFT) }}</p>
                        <a href="{{ route('service-orders.show', $invoice->service_order_id) }}" class="text-blue-600 text-sm hover:underline">Ver detalles de la orden</a>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Estado</p>
                        <x-badge variant="blue" class="text-sm px-4 py-1">EMITIDA</x-badge>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-slate-100">
                    <div class="flex justify-between items-center bg-slate-50 p-6 rounded-2xl">
                        <span class="text-xl font-bold text-slate-700">TOTAL A PAGAR</span>
                        <span class="text-3xl font-black text-slate-900">${{ number_format($invoice->amount, 2) }}</span>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Historial de Pagos</h3>
                </x-slot>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-emerald-50 flex items-center justify-center">
                            <i data-lucide="check" class="h-4 w-4 text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Pago registrado</p>
                            <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
