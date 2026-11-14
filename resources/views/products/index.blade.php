<x-app-layout>
<div class="ds">
    <x-page-header title="Inventario" subtitle="Productos, precios y stock de la sucursal">
        <x-slot name="actions">
            <x-button variant="secondary" href="{{ route('inventory.purchase') }}">Registrar compra</x-button>
            <x-button href="{{ route('products.create') }}">Nuevo producto</x-button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('products.index') }}" searchPlaceholder="Producto, categoría, código o proveedor">
        <x-select label="Categoría" name="category">
            <option value="">Todas</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" @selected(request('category') == $category)>{{ $category }}</option>
            @endforeach
        </x-select>
        <div class="flex items-end">
            <label for="low_stock" class="flex h-10 cursor-pointer items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="low_stock" id="low_stock" value="1" @checked(request('low_stock'))
                       class="size-4 cursor-pointer rounded border-input text-primary focus:ring-2 focus:ring-ring/30">
                Solo stock bajo
            </label>
        </div>
    </x-search-filter>

    <x-card flush>
        @if($products->isNotEmpty())
            <x-table :headers="['Producto', 'Proveedor', ['label' => 'Costo', 'align' => 'right'], ['label' => 'Venta', 'align' => 'right'], ['label' => 'Margen', 'align' => 'right'], ['label' => 'Stock', 'align' => 'right'], ['label' => '', 'align' => 'right']]">
                @foreach($products as $product)
                    @php
                        $profit = $product->sale_price - $product->purchase_price;
                        $profitPercent = $product->purchase_price > 0 ? round(($profit / $product->purchase_price) * 100) : null;
                    @endphp
                    <tr @class(['transition-colors hover:bg-muted/50', 'text-muted-foreground' => $product->trashed()])>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($product->trashed())
                                    <span class="font-medium">{{ $product->name }}</span>
                                    <x-status-badge>Inactivo</x-status-badge>
                                @else
                                    <a href="{{ route('products.show', $product) }}" class="font-medium text-primary hover:underline">{{ $product->name }}</a>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $product->category }}</p>
                        </td>
                        <td class="max-w-48 truncate px-4 py-3 text-muted-foreground" title="{{ $product->suppliers->pluck('name')->join(', ') }}">
                            {{ $product->suppliers->pluck('name')->join(', ') ?: '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-muted-foreground">{{ money($product->purchase_price) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ money($product->sale_price) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">
                            {{ money($profit) }}
                            @if($profitPercent !== null)
                                <span class="text-xs text-muted-foreground">({{ $profitPercent }} %)</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if($product->trashed() || ! $product->isLowStock())
                                <span class="tabular-nums">{{ $product->stock }}</span>
                            @else
                                <x-status-badge :tone="$product->stockTone()" :title="$product->stockLabel() . ' (mínimo ' . $product->min_stock . ')'">
                                    <span class="tabular-nums">{{ $product->stock }}</span>
                                </x-status-badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if($product->trashed())
                                    @can('admin')
                                        <form action="{{ route('products.restore', $product->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <x-button type="submit" variant="secondary" size="sm">Reactivar</x-button>
                                        </form>
                                    @endcan
                                @else
                                    <x-button variant="ghost" size="icon" href="{{ route('products.edit', $product) }}" title="Editar producto">
                                        <i data-lucide="pencil" class="size-4"></i>
                                        <span class="sr-only">Editar {{ $product->name }}</span>
                                    </x-button>
                                    @can('admin')
                                        <form action="{{ route('products.destroy', $product) }}" method="POST"
                                              data-confirm="¿Eliminar «{{ $product->name }}»?"
                                              data-confirm-description="Si ya tiene ventas o movimientos, queda desactivado en vez de eliminado, para conservar el historial."
                                              data-confirm-label="Eliminar producto"
                                              data-confirm-destructive>
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="ghost" size="icon" class="hover:!text-destructive-text" title="Eliminar producto">
                                                <i data-lucide="trash-2" class="size-4"></i>
                                                <span class="sr-only">Eliminar {{ $product->name }}</span>
                                            </x-button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="package">
                    {{ request()->except('page') ? 'Ningún producto coincide con la búsqueda.' : 'Aún no hay productos en el inventario.' }}
                    @unless(request()->except('page'))
                        <x-slot name="action"><x-button href="{{ route('products.create') }}">Nuevo producto</x-button></x-slot>
                    @endunless
                </x-empty-state>
            </div>
        @endif

        @if($products->hasPages())
            <x-slot name="footer">{{ $products->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
