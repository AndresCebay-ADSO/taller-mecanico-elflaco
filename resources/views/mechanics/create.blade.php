<x-app-layout>
    <x-page-header title="Nuevo Mecánico" subtitle="Registra un nuevo integrante para el taller.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('mechanics.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('mechanics.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre Completo" name="name" required placeholder="Ej. Juan Pérez" />
                    <x-input label="Teléfono" name="phone" required placeholder="Ej. 311 000 0000" />
                    <x-input label="Email" name="email" type="email" placeholder="juan@taller.com" />
                    <x-input label="Fecha de Contratación" name="hire_date" type="date" required value="{{ date('Y-m-d') }}" />
                    
                    <div>
                        <label for="is_active" class="block text-sm font-semibold text-slate-700 space-y-1.5">Estado</label>
                        <select id="is_active" name="is_active" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Registrar Mecánico
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
