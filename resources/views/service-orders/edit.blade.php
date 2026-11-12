<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    @php
        $orderNumber = '#' . str_pad($serviceOrder->id, 4, '0', STR_PAD_LEFT);
        $selectedStatus = old('status', $serviceOrder->status);
        $statusLabels = ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'];
    @endphp

    <x-page-header :title="'Editar orden ' . $orderNumber"
                   :back="route('service-orders.show', $serviceOrder)" :back-label="'Orden ' . $orderNumber" />

    <form action="{{ route('service-orders.update', $serviceOrder) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Estado">
            <div class="max-w-sm">
                <x-select label="Estado de la orden" name="status" required
                          :help="$serviceOrder->canTransitionTo('cancelled') && $serviceOrder->status !== 'cancelled' ? 'Al cancelar, los repuestos de sus trabajos vuelven al inventario.' : null">
                    @foreach($statusLabels as $value => $label)
                        @if($serviceOrder->canTransitionTo($value))
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endif
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="Cliente">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Nombre" name="customer_name" :value="$serviceOrder->customer_name" required maxlength="255" />
                <x-input label="Teléfono" name="customer_phone" type="tel" inputmode="tel" :value="$serviceOrder->customer_phone" maxlength="10" />
                <x-input label="C.C. o NIT" name="customer_document" :value="$serviceOrder->customer_document" placeholder="Opcional" maxlength="20"
                         help="Sale en el comprobante y en la orden de ingreso." />
            </div>
        </x-card>

        <x-card title="Moto y servicio">
            <div class="space-y-4">
                <x-input label="Vehículo" name="vehicle_info" :value="$serviceOrder->vehicle_info" required maxlength="255"
                         help="Marca, modelo, color y placa." />
                <x-textarea label="Motivo de ingreso" name="service_description" :value="$serviceOrder->service_description" required />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('service-orders.show', $serviceOrder) }}">Cancelar</x-button>
            <x-button type="submit">Guardar cambios</x-button>
        </div>
    </form>
</div>
</x-app-layout>
