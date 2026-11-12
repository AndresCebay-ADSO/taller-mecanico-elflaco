<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Nueva orden de servicio" subtitle="Registra la entrada de una moto al taller"
                   :back="route('service-orders.index')" back-label="Órdenes" />

    <form action="{{ route('service-orders.store') }}" method="POST" class="space-y-6">
        @csrf

        <x-card title="Cliente">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Nombre" name="customer_name" required placeholder="Ej.: Carlos Rodríguez" maxlength="255" />
                <x-input label="Teléfono" name="customer_phone" type="tel" inputmode="tel" placeholder="Ej.: 3004567890" maxlength="10" />
            </div>
        </x-card>

        <x-card title="Moto y servicio">
            <div class="space-y-4">
                <x-input label="Vehículo" name="vehicle_info" required maxlength="255" placeholder="Ej.: Pulsar NS 200 - Negra - XYZ12A"
                         help="Marca, modelo, color y placa." />
                <x-textarea label="Motivo de ingreso" name="service_description" required
                            placeholder="Qué le pasa a la moto o qué servicio pide el cliente" />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('service-orders.index') }}">Cancelar</x-button>
            <x-button type="submit">Crear orden</x-button>
        </div>
    </form>
</div>
</x-app-layout>
