<x-app-layout>
    <x-page-header title="Nueva Venta" subtitle="Registra una nueva venta de repuestos o servicios.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('sales.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('sales.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Monto Total" name="total" type="number" step="0.01" required placeholder="0.00" />
                    <x-input label="Fecha de Venta" name="sale_date" type="date" required value="{{ date('Y-m-d') }}" />
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Registrar Venta
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
