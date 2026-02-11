<x-app-layout>
    <x-page-header title="Editar Mecánico" subtitle="Actualiza la información de {{ $mechanic->name }}.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('mechanics.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('mechanics.update', $mechanic) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre Completo" name="name" required :value="$mechanic->name" />
                    <x-input label="Teléfono" name="phone" required :value="$mechanic->phone" />
                    <x-input label="Email" name="email" type="email" :value="$mechanic->email" />
                    <x-input label="Fecha de Contratación" name="hire_date" type="date" required :value="$mechanic->hire_date" />
                    
                    <div>
                        <label for="is_active" class="block text-sm font-semibold text-slate-700 space-y-1.5">Estado</label>
                        <select id="is_active" name="is_active" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="1" {{ $mechanic->is_active ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ !$mechanic->is_active ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Actualizar Mecánico
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
