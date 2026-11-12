<button
    @click="toggleTheme()"
    type="button"
    class="rounded-md p-2 text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-active cursor-pointer"
    :title="darkMode ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
>
    {{-- Envoltorio: Lucide reemplaza el <i> por un <svg>, así x-show queda en un elemento estable. --}}
    <span x-show="darkMode" class="block"><i data-lucide="sun" class="size-4"></i></span>
    <span x-show="!darkMode" class="block"><i data-lucide="moon" class="size-4"></i></span>
    <span class="sr-only" x-text="darkMode ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"></span>
</button>
