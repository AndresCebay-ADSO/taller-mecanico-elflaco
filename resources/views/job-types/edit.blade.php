<x-app-layout>
    <x-page-header title="Editar Tipo de Trabajo" subtitle="Modifica la definición de {{ $jobType->name }}.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('job-types.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('job-types.update', $jobType) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre del Servicio" name="name" required :value="$jobType->name" />
                    <x-input label="Precio Base" name="base_price" type="number" step="0.01" required :value="$jobType->base_price" />
                </div>
                
                <div class="space-y-1.5">
                    <label for="description" class="block text-sm font-semibold text-slate-700">Descripción <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="4" required class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm placeholder:text-slate-400">{{ $jobType->description }}</textarea>
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Actualizar Tipo de Trabajo
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
