@props([
    'action' => '',
    'method' => 'GET',
    'searchPlaceholder' => 'Buscar...',
])

@php
    $hasFilters = request()->except(['search', 'page']);
    $isOpen = !empty($hasFilters);
@endphp

<div x-data="{ open: @json($isOpen) }" class="mb-8">
    <form action="{{ $action }}" method="{{ $method }}" class="bg-white rounded-3xl shadow-theme-lg border border-slate-100 overflow-hidden transition-all duration-300 group/form">
        {{-- Toolbar Principal --}}
        <div class="p-4 lg:p-5 flex flex-col lg:flex-row items-center gap-4">
            {{-- Búsqueda Prominente (Gordito - Colores Refinados) --}}
            <div class="relative flex-1 w-full group">
                <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none transition-colors duration-300 group-focus-within:text-brand-500">
                    <i data-lucide="search" class="h-6 w-6 text-slate-400"></i>
                </div>
                
                <input 
                    type="text" 
                    name="search" 
                    id="search"
                    placeholder="{{ $searchPlaceholder }}" 
                    value="{{ request('search') }}" 
                    class="block w-full pl-16 pr-8 py-5 bg-transparent border border-slate-200 rounded-2xl text-base font-medium transition-all text-slate-800 placeholder:text-slate-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10"
                />
            </div>

            {{-- Botones de Acción Rápidos --}}
            <div class="flex items-center gap-3 w-full lg:w-auto">
                {{-- Toggle de Filtros Avanzados --}}
                @if($slot->isNotEmpty())
                    <button 
                        type="button" 
                        @click="open = !open"
                        :class="open ? 'bg-brand-50 text-brand-600 border-brand-200' : 'bg-white text-slate-700 border-slate-200'"
                        class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-6 py-5 border rounded-2xl text-sm font-bold transition-all hover:bg-slate-50 active:scale-95 shadow-theme-xs"
                    >
                        <i data-lucide="filter" class="h-5 w-5 transition-colors duration-300" :class="open ? 'text-brand-600' : 'text-slate-400'"></i>
                        Filtros
                    </button>
                @endif

                {{-- Limpiar --}}
                <a 
                    href="{{ url()->current() }}" 
                    class="inline-flex items-center justify-center p-5 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-2xl transition-all active:scale-95 bg-white border border-slate-200 shadow-theme-xs"
                    title="Limpiar filtros"
                >
                    <i data-lucide="rotate-ccw" class="h-6 w-6"></i>
                </a>

                {{-- Buscar --}}
                <button 
                    type="submit" 
                    class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-12 py-5 bg-brand-500 text-white rounded-2xl text-base font-black shadow-lg shadow-brand-500/30 hover:bg-brand-600 active:scale-95 transition-all"
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
                class="border-t border-gray-100 bg-gray-50/30 p-8"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </form>
</div>
