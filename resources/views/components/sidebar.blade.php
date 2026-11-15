@php
    $branchOptions = $availableBranches ?? collect();
    $activeBranch = $currentBranch ?? null;
@endphp

<aside id="app-sidebar"
       class="ds app-sidebar fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 -translate-x-full flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-transform duration-200 lg:static lg:translate-x-0"
       :class="navOpen && 'translate-x-0'">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <span class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                <i data-lucide="bike" class="size-4"></i>
            </span>
            <span class="flex flex-col">
                <span class="text-sm font-semibold leading-tight text-sidebar-active">MotoTaller</span>
                <span class="text-xs leading-tight text-sidebar-foreground">El Flaco</span>
            </span>
        </a>

        <div class="flex items-center">
            <x-theme-toggle />
            <button type="button" x-on:click="navOpen = false" class="cursor-pointer rounded-md p-2 text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-active lg:hidden">
                <i data-lucide="x" class="size-4"></i>
                <span class="sr-only">Cerrar menú</span>
            </button>
        </div>
    </div>

    <!-- Sucursal activa -->
    <div class="px-3 pb-3">
        @if($branchOptions->count() > 1)
            <form method="POST" action="{{ route('switch-branch') }}">
                @csrf
                <label for="branch-switcher" class="mb-1.5 block px-1 text-xs font-medium text-sidebar-foreground">Sucursal</label>
                <select id="branch-switcher" name="branch_id" onchange="this.form.submit()"
                    class="h-9 w-full cursor-pointer rounded-md border border-sidebar-border bg-sidebar-accent pl-3 pr-8 text-sm text-sidebar-active focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40">
                    @foreach($branchOptions as $branchOption)
                        <option value="{{ $branchOption->id }}" @selected($activeBranch?->id === $branchOption->id)>{{ $branchOption->name }}</option>
                    @endforeach
                </select>
            </form>
        @elseif($activeBranch)
            <div class="flex items-center gap-2 rounded-md border border-sidebar-border px-3 py-2">
                <i data-lucide="map-pin" class="size-4 shrink-0"></i>
                <span class="truncate text-sm text-sidebar-active">{{ $activeBranch->name }}</span>
            </div>
        @endif
    </div>

    <!-- Navigation -->
    <nav id="sidebar-nav" class="flex-1 space-y-0.5 overflow-y-auto px-3 py-2 custom-scrollbar">
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
            Ventas directas
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('invoices.index') }}" icon="file-text" :active="request()->routeIs('invoices*')">
            Facturas
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('cash.index') }}" icon="wallet" :active="request()->routeIs('cash*')">
            Caja
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('products.index') }}" icon="package" :active="request()->routeIs('products*')">
            Inventario
        </x-sidebar-link>

        @can('admin')
            <x-sidebar-link href="{{ route('inventory.index') }}" icon="history" :active="request()->routeIs('inventory*')">
                Trazabilidad
            </x-sidebar-link>
        @endcan

        <x-sidebar-link href="{{ route('mechanics.index') }}" icon="users" :active="request()->routeIs('mechanics*')">
            Mecánicos
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('suppliers.index') }}" icon="truck" :active="request()->routeIs('suppliers*')">
            Proveedores
        </x-sidebar-link>

        @can('admin')
            <x-sidebar-link href="{{ route('reports') }}" icon="bar-chart-3" :active="request()->routeIs('reports')">
                Reportes
            </x-sidebar-link>
        @endcan

        @can('admin')
            <p class="px-3 pb-1 pt-5 text-xs font-medium text-sidebar-foreground/70">Administración</p>

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
                Tipos de trabajo
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('imports.index') }}" icon="file-spreadsheet" :active="request()->routeIs('imports*')">
                Importar datos
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('settings') }}" icon="settings" :active="request()->routeIs('settings*')">
                Configuración
            </x-sidebar-link>
        @endcan
    </nav>

    <!-- Footer -->
    <div class="space-y-0.5 border-t border-sidebar-border p-3">
        <x-sidebar-link href="{{ route('profile.edit') }}" icon="user" :active="request()->routeIs('profile*')">
            {{ \Illuminate\Support\Str::limit(auth()->user()?->name, 24) }}
        </x-sidebar-link>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent/60 hover:text-sidebar-active cursor-pointer">
                <i data-lucide="log-out" class="size-4 shrink-0"></i>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
    {{-- El menú recuerda dónde quedó su scroll: al cambiar de página no salta arriba. Va al final del <aside>: así el menú ya tiene su alto real al restaurarlo. --}}
    <script>
        (() => {
            const nav = document.getElementById('sidebar-nav');
            try { nav.scrollTop = Number(sessionStorage.getItem('sidebarScroll')) || 0; } catch (e) {}
            window.addEventListener('pagehide', () => {
                try { sessionStorage.setItem('sidebarScroll', nav.scrollTop); } catch (e) {}
            });
        })();
    </script>
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
