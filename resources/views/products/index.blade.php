<x-app-layout>
    <x-page-header title="Inventario" subtitle="Gestión de productos y stock">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('products.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Producto
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input type="text" placeholder="Buscar por producto, categoría o proveedor..." class="w-full rounded-xl border border-slate-300 py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-xl border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm focus:border-blue-500 focus:outline-none cursor-pointer">
                <option>Todas las categorías</option>
            </select>
            <x-button variant="outline" size="sm">
                Stock Bajo
            </x-button>
        </div>
    </div>

    <x-card class="overflow-hidden p-0">
        <x-table :headers="['PRODUCTO', 'CATEGORÍA', 'PROVEEDOR', 'PRECIO COMPRA', 'PRECIO VENTA', 'GANANCIA', 'STOCK', 'ACCIONES']">
            @forelse($products as $product)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <i data-lucide="package" class="h-5 w-5"></i>
                            </div>
                            <div class="text-sm font-bold text-slate-900">{{ $product->name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <x-badge variant="slate">{{ $product->category }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600">
                        {{ $product->supplier->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-600">
                        ${{ number_format($product->purchase_price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        ${{ number_format($product->sale_price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $profit = $product->sale_price - $product->purchase_price;
                            $profitPercent = $product->purchase_price > 0 ? ($profit / $product->purchase_price) * 100 : 0;
                        @endphp
                        <div class="text-sm font-bold text-emerald-600">${{ number_format($profit, 0, ',', '.') }}</div>
                        <div class="text-[10px] font-bold text-emerald-500">({{ round($profitPercent) }}%)</div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $stockVariant = ($product->stock <= $product->min_stock) ? 'red' : 'emerald';
                        @endphp
                        <x-badge :variant="$stockVariant" class="px-3 py-1 rounded-full text-xs">
                            {{ $product->stock }} unidades
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('products.edit', $product) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar producto?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                        No hay productos registrados en el inventario.
                    </td>
                </tr>
            @endforelse
        </x-table>
        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $products->links() }}
        </div>
        @endif
    </x-card>
</x-app-layout>
