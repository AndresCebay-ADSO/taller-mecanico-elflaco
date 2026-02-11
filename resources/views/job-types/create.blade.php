<x-app-layout>
    <x-page-header title="Nuevo Tipo de Trabajo" subtitle="Define un nuevo servicio estándar para el taller.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('job-types.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('job-types.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre del Servicio" name="name" required placeholder="Ej. Cambio de Aceite" />
                    <x-input label="Precio Base" name="base_price" type="number" step="0.01" required placeholder="0.00" />
                </div>
                
                <div class="space-y-1.5">
                    <label for="description" class="block text-sm font-semibold text-slate-700">Descripción <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="4" required class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm placeholder:text-slate-400" placeholder="Describe brevemente qué incluye este servicio..."></textarea>
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Guardar Tipo de Trabajo
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
