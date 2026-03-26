<x-app-layout>
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Dashboard</h1>
        <p class="text-slate-500 font-medium">Vista general del taller</p>
    </div>

    <!-- 1. Banner de alerta (solo si hay stock bajo) -->
    @if($lowStockCount > 0)
    <div class="mb-8 flex items-center justify-between rounded-2xl bg-amber-50 dark:bg-amber-950/30 p-4 border border-amber-200 dark:border-amber-900/50">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400">
                <i data-lucide="alert-triangle" class="h-5 w-5"></i>
            </div>
            <p class="font-medium text-amber-800 dark:text-amber-200">
                <span class="font-bold">{{ $lowStockCount }}</span> producto(s) con stock bajo necesitan reposición
            </p>
        </div>
        <a href="{{ route('inventory.index') }}" class="text-sm font-bold text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 flex items-center gap-1 transition-colors">
            Ver inventario <i data-lucide="arrow-right" class="h-4 w-4"></i>
        </a>
    </div>
    @endif

    <!-- 3. Accesos rápidos (3 botones) - Moved before metrics for better UX or after, wait user said "2. Tarjetas, 3. Accesos", so let's put it after cards. But typical layout is Actions below header. Let's stick to user order. -->

    <!-- 2. Tarjetas de métricas (4 columnas) -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Ganancias Hoy -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="dollar-sign" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Ganancias Hoy</p>
                <p class="mt-2 text-4xl font-black text-slate-900 dark:text-white">${{ number_format($todayEarnings, 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">Ventas + órdenes hoy</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-emerald-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Ganancias del Mes -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="trending-up" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Ganancias del Mes</p>
                <p class="mt-2 text-4xl font-black text-slate-900 dark:text-white">${{ number_format($monthEarnings, 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">Mes actual</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Órdenes Activas -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                    <i data-lucide="clipboard-list" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Órdenes Activas</p>
                <p class="mt-2 text-4xl font-black text-slate-900 dark:text-white">{{ $activeOrders }}</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">
                    <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $pendingOrders }}</span> pendientes de entrega
                </p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-blue-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Total Productos -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400">
                    <i data-lucide="package" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Total Productos</p>
                <p class="mt-2 text-4xl font-black text-slate-900 dark:text-white">{{ $totalProducts }}</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">
                    <span class="text-amber-600 dark:text-amber-400 font-bold">{{ $lowStockCount }}</span> con stock bajo
                </p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-amber-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>
    </div>

    <!-- 3. Accesos rápidos -->
    <div class="mb-8 flex flex-wrap gap-4">
        <x-button href="{{ route('sales.create') }}" class="shadow-sm">
            <i data-lucide="shopping-cart" class="mr-2 h-4 w-4"></i> Nueva Venta
        </x-button>
        <x-button variant="secondary" href="{{ route('inventory.purchase') }}" class="shadow-sm">
            <i data-lucide="package-plus" class="mr-2 h-4 w-4"></i> Registrar Compra
        </x-button>
        <x-button variant="secondary" href="{{ route('service-orders.create') }}" class="shadow-sm">
            <i data-lucide="plus-circle" class="mr-2 h-4 w-4"></i> Nueva Orden
        </x-button>
    </div>

    <!-- 4. Grid de dos columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Izquierda: Últimas Ventas -->
        <x-card class="h-full">
            <x-slot name="header">
                <h3 class="font-bold text-slate-900 dark:text-white">Últimas Ventas</h3>
            </x-slot>
            @if($recentSales->count() > 0)
                <div class="space-y-4">
                    @foreach($recentSales as $sale)
                        @php
                            $badgeColor = match($sale->payment_method) {
                                'efectivo' => 'success',
                                'nequi' => 'blue',
                                'transferencia' => 'amber',
                                'tarjeta' => 'purple',
                                'daviplata' => 'teal',
                                default => 'gray'
                            };
                            $productsSummary = $sale->saleProducts->map(function($sp) {
                                return $sp->quantity . 'x ' . ($sp->product->name ?? 'Producto');
                            })->take(2)->implode(', ') . ($sale->saleProducts->count() > 2 ? ' ...' : '');
                        @endphp
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-gray-800/50 hover:bg-slate-100 dark:hover:bg-gray-800 transition-colors border border-transparent dark:border-gray-800">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                    <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white leading-none">
                                        {{ $sale->customer_name ?: ($sale->user->name ?? 'Cliente') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $productsSummary }} — {{ $sale->created_at->format('H:i') }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="font-black text-slate-900 dark:text-white">${{ number_format($sale->total_amount, 0, ',', '.') }}</span>
                                <x-badge variant="{{ $badgeColor }}">
                                    {{ ucfirst($sale->payment_method) }}
                                </x-badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-[200px] flex-col items-center justify-center text-center">
                    <div class="h-16 w-16 rounded-full bg-slate-50 dark:bg-gray-800 flex items-center justify-center mb-3">
                        <i data-lucide="shopping-bag" class="h-8 w-8 text-slate-300 dark:text-gray-600"></i>
                    </div>
                    <p class="text-slate-500 font-medium">No hay ventas recientes</p>
                </div>
            @endif
        </x-card>

        <!-- Derecha: Stock Bajo -->
        <x-card class="h-full">
            <x-slot name="header">
                <div class="flex justify-between items-center w-full">
                    <h3 class="font-bold text-slate-900 dark:text-white">Stock Bajo</h3>
                    <a href="{{ route('inventory.index') }}" class="text-sm font-medium text-amber-600 hover:text-amber-700 dark:text-amber-500 dark:hover:text-amber-400">Ver todo</a>
                </div>
            </x-slot>
            @if($lowStockProducts->count() > 0)
                <div class="space-y-6">
                    @foreach($lowStockProducts->take(6) as $product)
                        @php
                            $percent = $product->min_stock > 0 ? min(100, round(($product->stock / $product->min_stock) * 100)) : 0;
                            $barColor = $percent <= 20 ? 'bg-error-500' : ($percent <= 50 ? 'bg-warning-500' : 'bg-success-500');
                        @endphp
                        <div>
                            <div class="flex justify-between items-end mb-2">
                                <span class="font-bold text-sm text-slate-800 dark:text-slate-200">{{ $product->name }}</span>
                                <span class="text-xs font-bold text-slate-500">{{ $product->stock }} / {{ $product->min_stock }} min</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-gray-800 rounded-full h-2">
                                <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-[200px] flex-col items-center justify-center text-center">
                    <div class="h-16 w-16 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-3">
                        <i data-lucide="check-circle" class="h-8 w-8 text-emerald-500"></i>
                    </div>
                    <p class="text-slate-500 font-medium">Inventario saludable</p>
                </div>
            @endif
        </x-card>
    </div>

    <!-- 5. Grid de tres columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-10">
        <!-- Mecánicos activos (span 2) -->
        <x-card class="lg:col-span-2">
            <x-slot name="header">
                <h3 class="font-bold text-slate-900 dark:text-white">Mecánicos Activos del Mes</h3>
            </x-slot>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($mechanics as $mech)
                <div class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-gray-900/50 hover:bg-slate-50 dark:hover:bg-gray-800 transition-colors border border-slate-100 dark:border-gray-800 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-full bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center font-bold text-brand-700 dark:text-brand-400">
                            {{ strtoupper(substr($mech->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white leading-none">{{ $mech->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $mech->total_jobs }} trabajos del mes</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-emerald-600 dark:text-emerald-400">${{ number_format($mech->monthly_earnings, 0, ',', '.') }}</p>
                    </div>
                </div>
                @empty
                <div class="col-span-2 flex flex-col items-center justify-center text-center py-6">
                    <p class="text-slate-500 font-medium">No hay mecánicos activos.</p>
                </div>
                @endforelse
            </div>
        </x-card>

        <!-- Ventas por método de pago hoy -->
        <x-card>
            <x-slot name="header">
                <h3 class="font-bold text-slate-900 dark:text-white">Ventas por Método (Hoy)</h3>
            </x-slot>
            @if($todayByMethod->count() > 0)
                <div class="space-y-4">
                    @foreach($todayByMethod as $method)
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-gray-800/50 border border-slate-100 dark:border-gray-800">
                            <span class="font-bold text-sm text-slate-700 dark:text-slate-300 capitalize">{{ $method->payment_method }}</span>
                            <span class="font-black text-slate-900 dark:text-white">${{ number_format($method->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-full flex-col justify-center items-center py-8">
                    <p class="text-slate-500 font-medium">No hay ventas registradas hoy.</p>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
