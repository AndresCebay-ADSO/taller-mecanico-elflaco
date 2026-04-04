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
                    <h3 class="font-bold text-slate-900">Proveedores</h3>
                </x-slot>

                @php $lastSupplier = $product->lastSupplier(); @endphp

                @if($product->suppliers->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($product->suppliers as $supplier)
                            <div class="flex items-center gap-3 p-3 rounded-xl {{ $lastSupplier && $lastSupplier->id === $supplier->id ? 'bg-blue-50 border border-blue-200' : 'bg-slate-50' }}">
                                <div class="h-10 w-10 rounded-lg {{ $lastSupplier && $lastSupplier->id === $supplier->id ? 'bg-blue-100' : 'bg-slate-100' }} flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="truck" class="h-5 w-5 {{ $lastSupplier && $lastSupplier->id === $supplier->id ? 'text-blue-600' : 'text-slate-500' }}"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-900">{{ $supplier->name }}</p>
                                    @if($lastSupplier && $lastSupplier->id === $supplier->id)
                                        <span class="text-xs text-blue-600 font-semibold">Último proveedor</span>
                                    @endif
                                </div>
                                <a href="{{ route('suppliers.show', $supplier) }}" class="text-sm text-blue-600 hover:underline">Ver perfil</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">Sin proveedores asignados.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
