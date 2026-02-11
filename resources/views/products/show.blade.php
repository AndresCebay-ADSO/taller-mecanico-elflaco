<x-app-layout>
    <x-page-header title="{{ $product->name }}" subtitle="Detalle completo del producto y niveles de inventario.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('products.index') }}'">
                <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                Volver
            </x-button>
            <x-button variant="secondary" onclick="window.location.href='{{ route('products.edit', $product) }}'">
                <i data-lucide="pencil" class="mr-2 h-4 w-4"></i>
                Editar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-8">
            <x-card>
                <div class="flex flex-col md:flex-row justify-between gap-6">
                    <div class="space-y-4">
                        <div>
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoría</span>
                            <p class="text-lg font-bold text-slate-900">{{ $product->category }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">UPC / Código</span>
                            <p class="text-sm font-mono text-slate-600 bg-slate-50 px-2 py-1 rounded inline-block">{{ $product->upc ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-6 rounded-2xl flex flex-col items-end justify-center">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Precio de Venta</span>
                        <span class="text-4xl font-black text-blue-600">${{ number_format($product->sale_price, 2) }}</span>
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Análisis de Stock</h3>
                </x-slot>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-4 rounded-xl bg-slate-50">
                        <p class="text-xs font-semibold text-slate-500 uppercase">En Existencia</p>
                        <p class="text-2xl font-bold {{ $product->stock <= $product->min_stock ? 'text-red-600' : 'text-slate-900' }}">
                            {{ $product->stock }} unidades
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50">
                        <p class="text-xs font-semibold text-slate-500 uppercase">Mínimo Requerido</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $product->min_stock }} unidades</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50">
                        <p class="text-xs font-semibold text-slate-500 uppercase">Estado</p>
                        <div class="mt-1">
                            @if($product->stock <= $product->min_stock)
                                <x-badge variant="red">CRÍTICO / REABASTECER</x-badge>
                            @else
                                <x-badge variant="emerald">ÓPTIMO</x-badge>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-8">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Proveedor Asignado</h3>
                </x-slot>
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="truck" class="h-6 w-6 text-slate-600"></i>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900">{{ $product->supplier->name ?? 'Sin proveedor' }}</p>
                        <p class="text-xs text-slate-500">Última compra: {{ $product->updated_at->format('d/m/Y') }}</p>
                        <a href="{{ route('suppliers.show', $product->supplier_id) }}" class="mt-2 inline-block text-sm text-blue-600 hover:underline">Ver perfil del proveedor</a>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
