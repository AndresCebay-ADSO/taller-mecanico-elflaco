<x-app-layout>
    <x-page-header title="Orden #{{ str_pad($serviceOrder->id, 4, '0', STR_PAD_LEFT) }}" subtitle="Detalle completo de la orden de servicio.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('service-orders.index') }}'">
                <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                Volver
            </x-button>
            <x-button variant="secondary" onclick="window.location.href='{{ route('service-orders.edit', $serviceOrder) }}'">
                <i data-lucide="pencil" class="mr-2 h-4 w-4"></i>
                Editar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-8">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Descripción del Servicio</h3>
                </x-slot>
                <p class="text-slate-700 whitespace-pre-wrap">{{ $serviceOrder->service_description }}</p>
            </x-card>

            <!-- Aquí se podrían listar los trabajos realizados si existieran en la relación -->
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Trabajos y Repuestos</h3>
                        <x-badge variant="slate">Próximamente</x-badge>
                    </div>
                </x-slot>
                <div class="text-center py-8">
                    <i data-lucide="wrench" class="mx-auto h-12 w-12 text-slate-300"></i>
                    <p class="mt-4 text-slate-500">Módulo de adición de trabajos en desarrollo.</p>
                </div>
            </x-card>
        </div>

        <!-- Sidebar Details -->
        <div class="space-y-8">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Estado y Fechas</h3>
                </x-slot>
                <div class="space-y-4">
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado Actual</span>
                        <div class="mt-1">
                            @php
                                $badgeVariant = match($serviceOrder->status) {
                                    'pending' => 'amber',
                                    'in_progress' => 'blue',
                                    'completed' => 'emerald',
                                    'cancelled' => 'red',
                                    default => 'slate'
                                };
                            @endphp
                            <x-badge :variant="$badgeVariant" class="text-sm px-3 py-1 uppercase">
                                {{ $serviceOrder->status }}
                            </x-badge>
                        </div>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha de Entrada</span>
                        <p class="mt-1 text-sm text-slate-900 font-medium">
                            <i data-lucide="calendar" class="inline h-4 w-4 mr-1 text-slate-400"></i>
                            {{ $serviceOrder->created_at->format('d/m/Y h:i A') }}
                        </p>
                    </div>
                    @if($serviceOrder->started_at)
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Iniciado el</span>
                        <p class="mt-1 text-sm text-slate-900 font-medium font-mono">
                            {{ $serviceOrder->started_at->format('d/m/Y h:i A') }}
                        </p>
                    </div>
                    @endif
                </div>
            </x-card>

            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Información del Cliente</h3>
                </x-slot>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="user" class="h-5 w-5 text-slate-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $serviceOrder->customer_name }}</p>
                            <p class="text-xs text-slate-500">{{ $serviceOrder->customer_phone }}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Vehículo</span>
                        <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-700 font-medium italic">
                            {{ $serviceOrder->vehicle_info }}
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
