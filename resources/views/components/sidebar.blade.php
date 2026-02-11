<aside class="flex w-64 flex-col bg-[#1a1f2b] text-slate-300 transition-all duration-300">
    <!-- Header -->
    <div class="flex items-center gap-3 px-6 py-5">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-500/20">
            <i data-lucide="bike" class="h-7 w-7 text-white"></i>
        </div>
        <div class="flex flex-col">
            <span class="text-xl font-bold tracking-tight text-white leading-none">MotoTaller</span>
            <span class="mt-1 text-xs text-slate-400 font-medium tracking-wide leading-none">Sistema de Gestión</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-1 px-4 py-4 overflow-y-auto custom-scrollbar">
        <x-sidebar-link href="/dashboard" icon="layout-grid" active="{{ request()->is('dashboard') }}">
            Dashboard
        </x-sidebar-link>
        
        <x-sidebar-link href="/inventory" icon="package">
            Inventario
        </x-sidebar-link>

        <x-sidebar-link href="/service-orders" icon="clipboard-list" active="{{ request()->is('service-orders*') }}">
            Órdenes
        </x-sidebar-link>

        <x-sidebar-link href="/tasks" icon="wrench">
            Trabajos
        </x-sidebar-link>

        <x-sidebar-link href="/sales" icon="shopping-cart" active="{{ request()->is('sales*') }}">
            Ventas
        </x-sidebar-link>

        <x-sidebar-link href="/mechanics" icon="users" active="{{ request()->is('mechanics*') }}">
            Mecánicos
        </x-sidebar-link>

        <x-sidebar-link href="/suppliers" icon="truck" active="{{ request()->is('suppliers*') }}">
            Proveedores
        </x-sidebar-link>

        <x-sidebar-link href="/reports" icon="bar-chart-3">
            Reportes
        </x-sidebar-link>

        <x-sidebar-link href="/job-types" icon="settings-2">
            Tipos de Trabajo
        </x-sidebar-link>
    </nav>

    <!-- Footer -->
    <div class="border-t border-slate-700/50 p-4">
        <x-sidebar-link href="/settings" icon="settings">
            Configuración
        </x-sidebar-link>
    </div>
</aside>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>
