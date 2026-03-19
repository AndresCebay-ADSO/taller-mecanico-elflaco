<x-app-layout>
    <x-page-header title="Nuevo Proveedor" subtitle="Agrega un nuevo proveedor al sistema.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('suppliers.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('suppliers.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre" name="name" required placeholder="Ej. Repuestos El Valle" maxlength="30" />
                    <x-input label="Teléfono" name="phone" required placeholder="Ej. 3001234567"
                        type="tel" minlength="10" maxlength="10" pattern="[0-9]{10}"
                        title="Ingresa exactamente 10 dígitos numéricos" />
                    <x-input label="Email" name="email" type="email" placeholder="ejemplo@correo.com" maxlength="255" />
                    <x-input label="Dirección" name="address" placeholder="Ej. Calle 123 # 45-67" maxlength="50" />
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Guardar Proveedor
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>