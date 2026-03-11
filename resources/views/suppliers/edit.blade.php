<x-app-layout>
    <x-page-header title="Editar Proveedor" subtitle="Modifica la información del proveedor {{ $supplier->name }}.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('suppliers.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre" name="name" required :value="$supplier->name" />
                    <x-input label="Teléfono" name="phone" required :value="$supplier->phone"
                        type="tel" minlength="10" maxlength="10" pattern="[0-9]{10}"
                        title="Ingresa exactamente 10 dígitos numéricos" />
                    <x-input label="Email" name="email" type="email" :value="$supplier->email" />
                    <x-input label="Dirección" name="address" :value="$supplier->address" />
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Actualizar Proveedor
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>