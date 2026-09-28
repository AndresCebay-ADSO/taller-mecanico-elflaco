<x-app-layout>
{{-- El estado del modal vive aquí: <x-app-layout> no reenvía atributos como x-data. --}}
<div x-data="{ showJobModal: {{ $errors->any() && old('job_type_id') ? 'true' : 'false' }} }">
    <x-page-header title="Orden #{{ str_pad($serviceOrder->id, 4, '0', STR_PAD_LEFT) }}" subtitle="Detalle completo de la orden de servicio.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('service-orders.index') }}'">
                <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                Volver
            </x-button>
            <x-button variant="outline" onclick="window.location.href='{{ route('service-orders.edit', $serviceOrder) }}'">
                <i data-lucide="pencil" class="mr-2 h-4 w-4"></i>
                Editar
            </x-button>
            @if($serviceOrder->canReceiveJobs())
            <x-button variant="primary" @click="showJobModal = true">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Añadir Trabajo
            </x-button>
            @endif
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-8">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Motivo del Servicio</h3>
                </x-slot>
                <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $serviceOrder->service_description }}</p>
            </x-card>

            <!-- Trabajos Realizados -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Trabajos y Servicios</h3>
                    <x-badge variant="slate">{{ $serviceOrder->workshopJobs->count() }} tareas</x-badge>
                </div>

                @forelse($serviceOrder->workshopJobs as $job)
                <x-card class="overflow-hidden border-slate-200">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="flex-1 space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                        <i data-lucide="{{ $job->jobType->calculation_type === 'percentage' ? 'percent' : 'dollar-sign' }}" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 leading-none">{{ $job->jobType?->name ?? 'Servicio' }}</h4>
                                        <p class="mt-1 text-xs text-slate-500 font-medium italic">Responsable: {{ $job->mechanic?->name ?? 'Sin asignar' }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    @if(in_array($job->status, ['pending', 'in_progress'], true))
                                    <form action="{{ route('jobs.complete', $job) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Marcar como completado">
                                            <i data-lucide="check-circle" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @if($serviceOrder->invoices->isEmpty())
                                    <form action="{{ route('jobs.destroy', $job) }}" method="POST" onsubmit="return confirm('¿Eliminar este trabajo? Se devolverán los productos al stock.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-400 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar trabajo">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            
                            <p class="text-sm text-slate-600 px-1">{{ $job->description }}</p>
                            
                            <!-- Productos del Trabajo -->
                            @if($job->jobProducts->count() > 0)
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Repuestos y Materiales</span>
                                <div class="space-y-2">
                                    @foreach($job->jobProducts as $jp)
                                    <div class="flex justify-between items-center bg-white p-2 rounded-xl border border-slate-100 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="h-6 w-6 flex items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-600">{{ $jp->quantity }}x</span>
                                            <span class="text-xs font-bold text-slate-800">{{ $jp->product?->name ?? 'Producto eliminado' }}</span>
                                        </div>
                                        <span class="text-xs font-bold text-slate-900">${{ number_format($jp->total_price, 0, ',', '.') }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Cost Summary for this job -->
                        <div class="md:w-48 bg-slate-50/50 rounded-2xl p-4 border border-slate-100 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-[10px] font-bold text-slate-500 uppercase">
                                    <span>Mano de Obra</span>
                                    <span>${{ number_format($job->labor_cost, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px] font-bold text-slate-500 uppercase">
                                    <span>Productos</span>
                                    <span>${{ number_format($job->jobProducts->sum('total_price'), 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-200 mt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-800 uppercase">Subtotal</span>
                                    <span class="text-lg font-black text-blue-600">${{ number_format($job->total_amount + $job->jobProducts->sum('total_price'), 0, ',', '.') }}</span>
                                </div>
                                <div class="mt-2">
                                    @php
                                        $jobBadge = match($job->status) {
                                            'pending' => 'amber',
                                            'in_progress' => 'blue',
                                            'completed' => 'emerald',
                                            default => 'slate'
                                        };
                                    @endphp
                                    <x-badge :variant="$jobBadge" class="w-full justify-center py-1 uppercase text-[9px]">{{ $job->status_label }}</x-badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
                @empty
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 mx-auto mb-4">
                        <i data-lucide="wrench" class="h-8 w-8"></i>
                    </div>
                    <p class="text-slate-500 font-bold">No hay trabajos registrados en esta orden.</p>
                    @if($serviceOrder->canReceiveJobs())
                    <x-button variant="outline" size="sm" class="mt-4" @click="showJobModal = true">Añadir el primero</x-button>
                    @endif
                </div>
                @endforelse
            </div>
        </div>

        <!-- Sidebar Details -->
        <div class="space-y-8">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Resumen Financiero</h3>
                </x-slot>
                @php
                    $billableJobs = $serviceOrder->workshopJobs->where('status', '!=', 'cancelled');
                    $totalLabor = $billableJobs->sum('total_amount');
                    $totalProductsCost = $billableJobs->sum(fn ($j) => $j->jobProducts->sum('total_price'));
                    $invoice = $serviceOrder->invoices->first();
                    $canInvoice = !$invoice
                        && in_array($serviceOrder->status, ['in_progress', 'completed'], true)
                        && $billableJobs->isNotEmpty()
                        && $billableJobs->every(fn ($j) => $j->status === 'completed');
                @endphp
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-500">Mano de Obra Total:</span>
                        <span class="text-sm font-bold text-slate-900">${{ number_format($totalLabor, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-500">Repuestos Total:</span>
                        <span class="text-sm font-bold text-slate-900">${{ number_format($totalProductsCost, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <div class="bg-blue-600 rounded-2xl p-5 text-white">
                            <span class="text-[10px] font-black text-blue-200 uppercase tracking-widest">Total Orden</span>
                            <p class="text-3xl font-black">${{ number_format($totalLabor + $totalProductsCost, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @if($invoice)
                    <x-button variant="outline" class="w-full py-4" onclick="window.location.href='{{ route('invoices.show', $invoice) }}'">
                        <i data-lucide="file-text" class="mr-2 h-5 w-5"></i>
                        Ver factura {{ $invoice->invoice_number }}
                    </x-button>
                    @elseif($canInvoice)
                    <form action="{{ route('invoices.generate', $serviceOrder) }}" method="POST">
                        @csrf
                        <x-button variant="primary" type="submit" class="w-full py-4 shadow-lg shadow-blue-500/25">
                            <i data-lucide="file-text" class="mr-2 h-5 w-5"></i>
                            Generar Factura
                        </x-button>
                    </form>
                    @elseif($billableJobs->isNotEmpty() && $serviceOrder->canReceiveJobs())
                    <p class="text-xs font-medium text-slate-500 text-center">Completa todos los trabajos para poder facturar.</p>
                    @endif
                </div>
            </x-card>

            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Información del Cliente</h3>
                </x-slot>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="user" class="h-5 w-5 text-slate-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900 leading-tight">{{ $serviceOrder->customer_name }}</p>
                            <p class="text-xs text-slate-500 mt-1 font-medium">{{ $serviceOrder->customer_phone ?? 'Sin teléfono' }}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Vehículo Registrado</span>
                        <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-700 font-bold italic border border-slate-100 shadow-inner">
                            {{ $serviceOrder->vehicle_info }}
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    @if($serviceOrder->canReceiveJobs())
        @include('jobs.partials.job-modal', [
            'action' => route('service-orders.jobs.store', $serviceOrder),
            'serviceOrder' => $serviceOrder,
        ])
    @endif
</div>
</x-app-layout>
