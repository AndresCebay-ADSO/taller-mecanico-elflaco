@php
    $totalProducts = \App\Models\Product::count();
    $lowStockCount = \App\Models\Product::whereRaw('stock <= min_stock')->count();
    $lowStockProducts = \App\Models\Product::whereRaw('stock <= min_stock')->limit(3)->get();
    $activeMechanics = \App\Models\Mechanic::where('is_active', true)->limit(3)->get();
@endphp

<x-app-layout>
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Dashboard</h1>
        <p class="text-slate-500 font-medium">Vista general del taller</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-10">
        <!-- Total Products Card -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                    <i data-lucide="package" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Total Productos</p>
                <p class="mt-2 text-4xl font-black text-slate-900">{{ $totalProducts }}</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">
                    <span class="text-amber-600 font-bold">{{ $lowStockCount }}</span> con stock bajo
                </p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-amber-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Active Jobs Card -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <i data-lucide="wrench" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Trabajos Activos</p>
                <p class="mt-2 text-4xl font-black text-slate-900">0</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">0 total registrados</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-blue-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Today's Earnings Card -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="dollar-sign" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Ganancias Hoy</p>
                <p class="mt-2 text-4xl font-black text-slate-900">$0.00</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">0 trabajo(s)</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-emerald-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Month's Earnings Card -->
        <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-900/50 p-6 shadow-sm border border-slate-200 dark:border-gray-800 group">
            <div class="absolute top-0 right-0 p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <i data-lucide="trending-up" class="h-6 w-6"></i>
                </div>
            </div>
            <div class="relative">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Ganancias del Mes</p>
                <p class="mt-2 text-4xl font-black text-slate-900">$0.00</p>
                <p class="mt-1 text-sm text-slate-500 font-medium">Taller: $0.00</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1.5 w-full bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <!-- Left Side: Alerts and Mechanics -->
        <div class="space-y-8">
            <!-- Stock Alerts -->
            @if($lowStockCount > 0)
            <div class="rounded-3xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400">
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-amber-900 dark:text-amber-200">Stock Bajo</h3>
                        <p class="text-sm text-amber-700 dark:text-amber-400 font-medium">{{ $lowStockCount }} producto(s) requieren reabastecimiento</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @foreach($lowStockProducts as $lp)
                    <div class="flex items-center justify-between rounded-2xl bg-white dark:bg-gray-900/50 p-4 shadow-sm border border-amber-100 dark:border-amber-900/20">
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $lp->name }}</span>
                        <x-badge variant="error">Stock: {{ $lp->stock }} / Mín: {{ $lp->min_stock }}</x-badge>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Active Mechanics -->
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Mecánicos Activos</h3>
                </x-slot>
                <div class="space-y-4">
                    @forelse($activeMechanics as $mech)
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700">
                                {{ strtoupper(substr($mech->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 leading-none">{{ $mech->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">0 trabajos realizados</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-emerald-600">$0.00</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">acumulado</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-center py-6 text-slate-500">No hay mecánicos activos registrados.</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        <!-- Right Side: Recent Activity -->
        <x-card class="h-full">
            <x-slot name="header">
                <h3 class="font-bold text-slate-900">Trabajos Recientes</h3>
            </x-slot>
            <div class="flex h-[400px] flex-col items-center justify-center text-center">
                <div class="h-20 w-20 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                    <i data-lucide="layers" class="h-10 w-10 text-slate-200"></i>
                </div>
                <p class="text-slate-400 font-medium">No hay trabajos registrados</p>
            </div>
        </x-card>
    </div>
</x-app-layout>
