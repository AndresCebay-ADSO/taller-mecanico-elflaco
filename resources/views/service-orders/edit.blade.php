<x-app-layout>
    <x-page-header title="Editar Orden de Servicio #{{ str_pad($serviceOrder->id, 4, '0', STR_PAD_LEFT) }}" subtitle="Actualiza la información de la orden de servicio.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('service-orders.show', $serviceOrder) }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-4xl">
        <x-card>
            <form action="{{ route('service-orders.update', $serviceOrder) }}" method="POST" class="space-y-8">
                @csrf
                @method('PUT')
                
                <!-- Status and General Section -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="settings" class="h-5 w-5 text-blue-600"></i>
                        Estado de la Orden
                    </h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="status" class="block text-sm font-semibold text-slate-700">Estado <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="pending" {{ $serviceOrder->status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="in_progress" {{ $serviceOrder->status === 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                                <option value="completed" {{ $serviceOrder->status === 'completed' ? 'selected' : '' }}>Completado</option>
                                <option value="cancelled" {{ $serviceOrder->status === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Customer Section -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="user" class="h-5 w-5 text-blue-600"></i>
                        Información del Cliente
                    </h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <x-input label="Nombre del Cliente" name="customer_name" value="{{ $serviceOrder->customer_name }}" required placeholder="Ej. Carlos Rodríguez" />
                        <x-input label="Teléfono de Contacto" name="customer_phone" value="{{ $serviceOrder->customer_phone }}" placeholder="Ej. 300 456 7890" />
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Vehicle Section -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="bike" class="h-5 w-5 text-blue-600"></i>
                        Información del Vehículo
                    </h3>
                    <x-input label="Detalles del Vehículo" name="vehicle_info" value="{{ $serviceOrder->vehicle_info }}" required placeholder="Ej. Pulsar NS 200 - Negra - Placa XYZ123" />
                </div>

                <hr class="border-slate-100">

                <!-- Service Section -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="clipboard-list" class="h-5 w-5 text-blue-600"></i>
                        Descripción del Servicio
                    </h3>
                    <div class="space-y-1.5">
                        <label for="service_description" class="block text-sm font-semibold text-slate-700">Motivo de Ingreso / Fallas Reportadas <span class="text-red-500">*</span></label>
                        <textarea id="service_description" name="service_description" rows="4" required class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm placeholder:text-slate-400" placeholder="Describe el problema o el servicio solicitado...">{{ $serviceOrder->service_description }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Actualizar Orden de Servicio
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
