<button 
    @click="toggleTheme()" 
    type="button" 
    class="relative p-2 rounded-xl border border-gray-200 bg-white shadow-theme-xs transition-all hover:bg-gray-50 active:scale-95"
    title="Cambiar tema"
>
    {{-- Sol (Modo Oscuro -> Pasa a Claro) --}}
    <div x-show="darkMode" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 rotate-90 scale-50" x-transition:enter-end="opacity-100 rotate-0 scale-100">
        <i data-lucide="sun" class="h-5 w-5 text-warning-500"></i>
    </div>
    
    {{-- Luna (Modo Claro -> Pasa a Oscuro) --}}
    <div x-show="!darkMode" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -rotate-90 scale-50" x-transition:enter-end="opacity-100 rotate-0 scale-100">
        <i data-lucide="moon" class="h-5 w-5 text-gray-500"></i>
    </div>
</button>
