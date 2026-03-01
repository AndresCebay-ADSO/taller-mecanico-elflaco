<x-app-layout>
    <x-page-header title="Reportes" subtitle="Resumen de {{ now()->translatedFormat('F Y') }}">
    </x-page-header>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <x-card class="bg-white border-blue-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Ingresos Totales</span>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <i data-lucide="dollar-sign" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900">${{ number_format($totalIncome, 0, ',', '.') }}</p>
            <p class="mt-1 text-[10px] text-slate-400 font-bold">Trabajos: $0 | Ventas: ${{ number_format($totalIncome, 0, ',', '.') }}</p>
        </x-card>

        <x-card class="bg-white border-blue-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Ganancia del Taller</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <i data-lucide="trending-up" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600">${{ number_format($workshopProfit, 0, ',', '.') }}</p>
            <p class="mt-1 text-[10px] text-slate-400 font-bold">Trabajos + margen de ventas</p>
        </x-card>

        <x-card class="bg-white border-blue-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Trabajos del Mes</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <i data-lucide="wrench" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900">{{ $monthlyJobs }}</p>
            <p class="mt-1 text-[10px] text-slate-400 font-bold">0 completados</p>
        </x-card>

        <x-card class="bg-white border-blue-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Pagos a Mecánicos</span>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                    <i data-lucide="users" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-amber-600">$0.00</p>
            <p class="mt-1 text-[10px] text-slate-400 font-bold">Total pagado a mecánicos</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
        <!-- Weekly Income Chart Placeholder -->
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900">Ingresos de la Semana</h3>
                </div>
            </x-slot>
            <div class="flex items-end justify-between gap-2 h-48 px-4">
                @foreach(['lun','mar','mié','jue','vie','sáb','dom'] as $day)
                <div class="flex flex-col items-center flex-1">
                    <div class="w-full bg-slate-100 rounded-t-lg h-32 relative">
                        <div class="absolute bottom-0 w-full bg-blue-100 rounded-t-lg h-4 transition-all hover:h-12 hover:bg-blue-200"></div>
                    </div>
                    <span class="mt-2 text-[10px] font-bold text-slate-400 uppercase">{{ $day }}</span>
                    <span class="text-[10px] font-bold text-slate-300">$0</span>
                </div>
                @endforeach
            </div>
        </x-card>

        <!-- Earnings by Mechanic -->
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900">Ganancias por Mecánico</h3>
                </div>
            </x-slot>
            <div class="space-y-6">
                @foreach($mechanics as $index => $mech)
                <div class="flex items-center gap-4">
                    <div class="h-8 w-8 rounded-full bg-blue-50 flex items-center justify-center text-xs font-bold text-blue-600">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-bold text-slate-700">{{ $mech->name }}</span>
                            <span class="text-sm font-bold text-emerald-600">$0.00</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="bg-blue-400 h-1.5 rounded-full" style="width: 10%"></div>
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400">0 trabajo(s)</p>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <!-- Most Sold Products -->
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="package" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900">Productos Más Vendidos</h3>
                </div>
            </x-slot>
            <div class="flex h-32 flex-col items-center justify-center text-center">
                <p class="text-slate-400 font-medium italic">Sin datos</p>
            </div>
        </x-card>

        <!-- Inventory Status -->
        <x-card>
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <i data-lucide="archive" class="h-5 w-5 text-blue-600"></i>
                    <h3 class="font-bold text-slate-900">Estado del Inventario</h3>
                </div>
            </x-slot>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 rounded-2xl bg-slate-50 text-center">
                    <p class="text-3xl font-black text-slate-900">{{ $totalProducts }}</p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total Productos</p>
                </div>
                <div class="p-4 rounded-2xl bg-amber-50 text-center">
                    <p class="text-3xl font-black text-amber-600">{{ $lowStockCount }}</p>
                    <p class="text-[10px] font-bold text-amber-500 uppercase tracking-widest">Stock Bajo</p>
                </div>
            </div>
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-500">Valor del Inventario</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-slate-700">Costo total:</span>
                    <span class="text-sm font-black text-slate-900">${{ number_format($inventoryCostTotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl">
                    <span class="text-sm font-bold text-emerald-800">Valor de venta:</span>
                    <span class="text-lg font-black text-emerald-600">${{ number_format($inventorySaleTotal, 0, ',', '.') }}</span>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
