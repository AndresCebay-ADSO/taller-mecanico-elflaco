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
        <x-sidebar-link href="{{ route('dashboard') }}" icon="layout-grid" :active="request()->routeIs('dashboard')">
            Dashboard
        </x-sidebar-link>
        
        <x-sidebar-link href="{{ route('inventory.index') }}" icon="history" :active="request()->routeIs('inventory.index')">
            Trazabilidad
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('products.index') }}" icon="package" :active="request()->routeIs('products.index')">
            Inventario
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('service-orders.index') }}" icon="clipboard-list" :active="request()->routeIs('service-orders*')">
            Órdenes
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('jobs.index') }}" icon="wrench" :active="request()->routeIs('jobs*')">
            Trabajos
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('sales.index') }}" icon="shopping-cart" :active="request()->routeIs('sales*')">
            Ventas Directas
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('mechanics.index') }}" icon="users" :active="request()->routeIs('mechanics*')">
            Mecánicos
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('suppliers.index') }}" icon="truck" :active="request()->routeIs('suppliers*')">
            Proveedores
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('reports') }}" icon="bar-chart-3" :active="request()->routeIs('reports')">
            Reportes
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('job-types.index') }}" icon="settings-2" :active="request()->routeIs('job-types*')">
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
