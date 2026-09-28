@php
    $branchOptions = $availableBranches ?? collect();
    $activeBranch = $currentBranch ?? null;
@endphp

<aside class="flex w-72 flex-col bg-gray-950 text-gray-400 transition-all duration-300 border-r border-gray-900">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-8">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500 shadow-lg shadow-brand-500/20">
                <i data-lucide="bike" class="h-6 w-6 text-white"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-xl font-black tracking-tight text-white leading-none">MotoTaller</span>
                <span class="mt-1 text-[10px] text-gray-400 font-bold uppercase tracking-widest leading-none">Management</span>
            </div>
        </div>

        <x-theme-toggle />
    </div>

    <!-- Sucursal activa -->
    <div class="px-4 pb-4">
        @if($branchOptions->count() > 1)
            <form method="POST" action="{{ route('switch-branch') }}">
                @csrf
                <label for="branch-switcher" class="mb-1.5 block px-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Sucursal</label>
                <select id="branch-switcher" name="branch_id" onchange="this.form.submit()"
                    class="w-full cursor-pointer rounded-xl border border-gray-800 bg-gray-900 px-3 py-2.5 text-sm font-bold text-white focus:border-brand-500 focus:ring-brand-500">
                    @foreach($branchOptions as $branchOption)
                        <option value="{{ $branchOption->id }}" @selected($activeBranch?->id === $branchOption->id)>{{ $branchOption->name }}</option>
                    @endforeach
                </select>
            </form>
        @elseif($activeBranch)
            <div class="flex items-center gap-2 rounded-xl border border-gray-800 bg-gray-900 px-3 py-2.5">
                <i data-lucide="map-pin" class="h-4 w-4 shrink-0 text-brand-400"></i>
                <span class="truncate text-sm font-bold text-white">{{ $activeBranch->name }}</span>
            </div>
        @endif
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-1.5 px-4 py-2 overflow-y-auto custom-scrollbar">
        <x-sidebar-link href="{{ route('dashboard') }}" icon="layout-grid" :active="request()->routeIs('dashboard')">
            Dashboard
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

        <x-sidebar-link href="{{ route('invoices.index') }}" icon="file-text" :active="request()->routeIs('invoices*')">
            Facturas
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('products.index') }}" icon="package" :active="request()->routeIs('products*')">
            Inventario
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('inventory.index') }}" icon="history" :active="request()->routeIs('inventory*')">
            Trazabilidad
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

        @can('admin')
            <p class="px-4 pt-6 pb-2 text-[10px] font-bold uppercase tracking-widest text-gray-600">Administración</p>

            <x-sidebar-link href="{{ route('users.index') }}" icon="shield" :active="request()->routeIs('users*')">
                Usuarios
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('branches.index') }}" icon="building-2" :active="request()->routeIs('branches*')">
                Sucursales
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('branch-transfers.index') }}" icon="arrow-left-right" :active="request()->routeIs('branch-transfers*')">
                Transferencias
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('job-types.index') }}" icon="settings-2" :active="request()->routeIs('job-types*')">
                Tipos de Trabajo
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('settings') }}" icon="settings" :active="request()->routeIs('settings*')">
                Configuración
            </x-sidebar-link>
        @endcan
    </nav>

    <!-- Footer -->
    <div class="border-t border-gray-900 p-4 space-y-1">
        <x-sidebar-link href="{{ route('profile.edit') }}" icon="user" :active="request()->routeIs('profile*')">
            {{ \Illuminate\Support\Str::limit(auth()->user()?->name, 24) }}
        </x-sidebar-link>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium text-gray-400 transition-colors hover:bg-red-500/10 hover:text-red-400 group cursor-pointer">
                <i data-lucide="log-out" class="h-4 w-4 shrink-0"></i>
                <span>Cerrar Sesión</span>
            </button>
        </form>
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
