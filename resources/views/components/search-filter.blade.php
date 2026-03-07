@props([
    'action' => '',
    'method' => 'GET',
    'searchPlaceholder' => 'Buscar...',
])

@php
    $hasFilters = request()->except(['search', 'page']);
    $isOpen = !empty($hasFilters);
@endphp

<div x-data="{ open: @json($isOpen) }" class="mb-6">
    <form action="{{ $action }}" method="{{ $method }}" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300">
        {{-- Toolbar Principal --}}
        <div class="p-4 flex flex-col md:flex-row items-center gap-4">
            {{-- Búsqueda Prominente --}}
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i data-lucide="search" class="h-6 w-6 text-slate-400"></i>
                </div>
                <input 
                    type="text" 
                    name="search" 
                    id="search"
                    placeholder="{{ $searchPlaceholder }}" 
                    value="{{ request('search') }}" 
                    class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base transition-all placeholder:text-slate-400"
                />
            </div>

            {{-- Botones de Acción Rápidos --}}
            <div class="flex items-center gap-3 w-full md:w-auto">
                {{-- Toggle de Filtros Avanzados (Solo si hay contenido) --}}
                @if($slot->isNotEmpty())
                    <button 
                        type="button" 
                        @click="open = !open"
                        :class="open ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-white text-slate-600 border-slate-200'"
                        class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-6 py-4 border rounded-2xl text-sm font-bold transition-all hover:bg-slate-50 active:scale-95 shadow-sm"
                    >
                        <i data-lucide="filter" class="h-5 w-5" :class="open ? 'text-blue-600' : 'text-slate-400'"></i>
                        Filtros
                        <i data-lucide="chevron-down" class="h-5 w-5 transition-transform duration-200" :class="open && 'rotate-180'"></i>
                    </button>
                @endif

                {{-- Limpiar --}}
                <a 
                    href="{{ url()->current() }}" 
                    class="inline-flex items-center justify-center p-4 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-2xl transition-all active:scale-95 bg-white border border-slate-200 shadow-sm"
                    title="Limpiar filtros"
                >
                    <i data-lucide="rotate-ccw" class="h-6 w-6"></i>
                </a>

                {{-- Buscar --}}
                <button 
                    type="submit" 
                    class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-8 py-4 bg-blue-600 text-white rounded-2xl text-base font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 active:scale-95 transition-all"
                >
                    Buscar
                </button>
            </div>
        </div>

        {{-- Panel de Filtros Avanzados (Solo si hay contenido) --}}
        @if($slot->isNotEmpty())
            <div 
                x-show="open" 
                x-collapse
                x-cloak
                class="border-t border-slate-100 bg-slate-50/50 p-6"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </form>
</div>
