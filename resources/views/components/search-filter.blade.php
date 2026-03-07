@props([
    'action' => '',
    'method' => 'GET',
    'searchPlaceholder' => 'Buscar...',
])

<form action="{{ $action }}" method="{{ $method }}" class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
    <div class="flex flex-col md:flex-row gap-4 items-end">
        {{-- Búsqueda por texto (Opcional, pero común en todos) --}}
        <div class="flex-1 w-full">
            <x-input 
                type="text" 
                name="search" 
                label="Búsqueda" 
                placeholder="{{ $searchPlaceholder }}" 
                value="{{ request('search') }}" 
            />
        </div>

        {{-- Slot para filtros específicos de cada módulo (Selects, Fechas, etc) --}}
        {{ $slot }}

        {{-- Botones de acción --}}
        <div class="flex items-center gap-2">
            <x-button type="submit" variant="primary">
                <i data-lucide="search" class="mr-2 h-4 w-4"></i>
                Filtrar
            </x-button>
            <x-button type="button" variant="secondary" onclick="window.location.href='{{ url()->current() }}'">
                <i data-lucide="x" class="mr-2 h-4 w-4"></i>
                Limpiar
            </x-button>
        </div>
    </div>
</form>
