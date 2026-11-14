{{-- Barra de búsqueda y filtros sobre un listado (DESIGN.md §7). Los filtros extra van en el slot. --}}
@props([
    'action' => '',
    'method' => 'GET',
    'searchPlaceholder' => 'Buscar...',
])

@php
    $hasFilters = request()->except(['search', 'page']);
    $isOpen = !empty($hasFilters);
@endphp

<div x-data="{ open: @json($isOpen) }" class="ds mb-4">
    <form action="{{ $action }}" method="{{ $method }}" class="rounded-lg border border-border bg-card shadow-xs">
        <div class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center">
            <label for="search" class="sr-only">Buscar</label>
            <div class="relative flex-1">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"></i>
                <input
                    type="search"
                    name="search"
                    id="search"
                    placeholder="{{ $searchPlaceholder }}"
                    value="{{ request('search') }}"
                    class="block h-10 w-full rounded-md border border-input bg-card pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30"
                />
            </div>

            <div class="flex items-center gap-2">
                @if($slot->isNotEmpty())
                    <x-button variant="secondary" @click="open = !open" ::aria-expanded="open.toString()" ::class="open && 'bg-muted'">
                        <i data-lucide="sliders-horizontal" class="size-4"></i>
                        Filtros
                    </x-button>
                @endif

                @if(request()->except('page'))
                    <x-button variant="ghost" size="icon" href="{{ url()->current() }}" title="Limpiar filtros">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                        <span class="sr-only">Limpiar filtros</span>
                    </x-button>
                @endif

                <x-button type="submit" class="flex-1 sm:flex-none">Buscar</x-button>
            </div>
        </div>

        @if($slot->isNotEmpty())
            <div x-show="open" x-cloak class="border-t border-border p-3">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </form>
</div>
