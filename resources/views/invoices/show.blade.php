@php
    $order = $invoice->serviceOrder;
    $jobs = $order->workshopJobs->where('status', '!=', 'cancelled');
    $laborTotal = $jobs->sum('total_amount');
    $partsTotal = $jobs->sum(fn ($job) => $job->jobProducts->sum('total_price'));
    $money = fn ($value) => '$' . number_format((float) $value, 0, ',', '.');
@endphp

<x-app-layout>
    <div class="no-print">
        <x-page-header title="Factura {{ $invoice->invoice_number }}" subtitle="Orden #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }} · {{ $order->customer_name }}">
            <x-slot name="actions">
                <x-button variant="outline" onclick="window.location.href='{{ route('invoices.index') }}'">
                    <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                    Volver
                </x-button>
                <x-button variant="outline" onclick="window.location.href='{{ route('service-orders.show', $order) }}'">
                    <i data-lucide="clipboard-list" class="mr-2 h-4 w-4"></i>
                    Ver orden
                </x-button>
                @can('admin')
                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline"
                        onsubmit="return confirm('¿Anular la factura {{ $invoice->invoice_number }}? La orden podrá facturarse de nuevo.')">
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" type="submit">
                            <i data-lucide="ban" class="mr-2 h-4 w-4"></i>
                            Anular
                        </x-button>
                    </form>
                @endcan
                <x-button variant="primary" onclick="window.print()">
                    <i data-lucide="printer" class="mr-2 h-4 w-4"></i>
                    Imprimir
                </x-button>
            </x-slot>
        </x-page-header>
    </div>

    <div class="invoice-sheet mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm md:p-12 dark:border-gray-800 dark:bg-gray-900">
        {{-- Encabezado --}}
        <div class="flex flex-col gap-6 border-b border-slate-200 pb-8 md:flex-row md:items-start md:justify-between dark:border-gray-800">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ $settings['workshop_name'] ?? config('app.name') }}</h2>
                <div class="mt-2 space-y-0.5 text-sm text-slate-500 dark:text-gray-400">
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
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Factura de servicio</p>
                <p class="mt-1 font-mono text-2xl font-black text-slate-900 dark:text-white">{{ $invoice->invoice_number }}</p>
                <p class="mt-2 text-sm text-slate-500">Fecha: <span class="font-bold text-slate-700 dark:text-gray-300">{{ $invoice->invoice_date->format('d/m/Y') }}</span></p>
                <p class="text-sm text-slate-500">Orden: <span class="font-bold text-slate-700 dark:text-gray-300">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span></p>
            </div>
        </div>

        {{-- Cliente y vehículo --}}
        <div class="grid grid-cols-1 gap-6 border-b border-slate-200 py-8 md:grid-cols-2 dark:border-gray-800">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Cliente</p>
                <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ $order->customer_name }}</p>
                <p class="text-sm text-slate-500">{{ $order->customer_phone ?: 'Sin teléfono' }}</p>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Vehículo</p>
                <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ $order->vehicle_info }}</p>
                <p class="text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($order->service_description, 140) }}</p>
            </div>
        </div>

        {{-- Detalle --}}
        <div class="overflow-x-auto py-8">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-[10px] font-black uppercase tracking-widest text-slate-400 dark:border-gray-800">
                        <th class="pb-3 pr-4">Concepto</th>
                        <th class="px-4 pb-3 text-right">Cant.</th>
                        <th class="px-4 pb-3 text-right">Valor unit.</th>
                        <th class="pb-3 pl-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                    @foreach($jobs as $job)
                        <tr>
                            <td class="py-3 pr-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $job->jobType?->name ?? 'Servicio' }}</p>
                                <p class="text-xs text-slate-500">{{ $job->description }} · Mecánico: {{ $job->mechanic?->name ?? 'Sin asignar' }}</p>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600 dark:text-gray-400">1</td>
                            <td class="px-4 py-3 text-right text-slate-600 dark:text-gray-400">{{ $money($job->total_amount) }}</td>
                            <td class="py-3 pl-4 text-right font-bold text-slate-900 dark:text-white">{{ $money($job->total_amount) }}</td>
                        </tr>
                        @foreach($job->jobProducts as $part)
                            <tr>
                                <td class="py-3 pl-4 pr-4">
                                    <p class="text-slate-700 dark:text-gray-300">{{ $part->product?->name ?? 'Producto eliminado' }}</p>
                                    <p class="text-xs text-slate-400">Repuesto</p>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-gray-400">{{ $part->quantity }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-gray-400">{{ $money($part->unit_price) }}</td>
                                <td class="py-3 pl-4 text-right font-bold text-slate-900 dark:text-white">{{ $money($part->total_price) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totales --}}
        <div class="flex justify-end border-t border-slate-200 pt-6 dark:border-gray-800">
            <dl class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Mano de obra</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $money($laborTotal) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Repuestos</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $money($partsTotal) }}</dd>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-3 dark:border-gray-800">
                    <dt class="text-base font-black text-slate-900 dark:text-white">Total</dt>
                    <dd class="text-2xl font-black text-slate-900 dark:text-white">{{ $money($invoice->amount) }}</dd>
                </div>
            </dl>
        </div>

        @if(!empty($settings['footer_text_invoice']))
            <p class="mt-10 border-t border-dashed border-slate-200 pt-6 text-center text-xs text-slate-500 dark:border-gray-800">
                {{ $settings['footer_text_invoice'] }}
            </p>
        @endif
    </div>
</x-app-layout>
