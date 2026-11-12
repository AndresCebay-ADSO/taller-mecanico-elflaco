<x-app-layout>
<div class="ds">
    <x-page-header :title="$product->name" :subtitle="$product->category"
                   :back="route('products.index')" back-label="Inventario">
        <x-slot name="badge">
            <x-status-badge :tone="$product->stockTone()">{{ $product->stockLabel() }}</x-status-badge>
        </x-slot>
        <x-slot name="actions">
            <x-button variant="secondary" href="{{ route('products.edit', $product) }}">Editar</x-button>
            <x-button href="{{ route('inventory.purchase') }}">Registrar compra</x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                    <p class="text-sm font-medium text-muted-foreground">Precio de venta</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums">{{ money($product->sale_price) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                    <p class="text-sm font-medium text-muted-foreground">En existencia</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums {{ $product->isLowStock() ? \App\Support\Tone::text($product->stockTone()) : '' }}">{{ $product->stock }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">unidades</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-5 shadow-xs">
                    <p class="text-sm font-medium text-muted-foreground">Stock mínimo</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums">{{ $product->min_stock }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">avisa al llegar aquí</p>
                </div>
            </div>

            <x-card title="Datos del producto">
                <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Categoría</dt>
                        <dd class="mt-0.5">{{ $product->category }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Código / UPC</dt>
                        <dd class="mt-0.5 break-all font-mono">{{ $product->upc ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Costo de referencia</dt>
                        <dd class="mt-0.5 tabular-nums">{{ money($product->purchase_price) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Trazabilidad</dt>
                        <dd class="mt-0.5"><a href="{{ route('inventory.index', ['search' => $product->name]) }}" class="text-primary hover:underline">Ver lotes y movimientos</a></dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card title="Proveedores" flush class="self-start">
            @php $lastSupplier = $product->lastSupplier(); @endphp
            @if($product->suppliers->isNotEmpty())
                <ul class="divide-y divide-border">
                    @foreach($product->suppliers as $supplier)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium" title="{{ $supplier->name }}">{{ $supplier->name }}</p>
                                @if($lastSupplier && $lastSupplier->id === $supplier->id)
                                    <p class="text-xs text-muted-foreground">Última compra</p>
                                @endif
                            </div>
                            <a href="{{ route('suppliers.index', ['search' => $supplier->name]) }}" class="shrink-0 text-sm text-primary hover:underline">Ver</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-5">
                    <x-empty-state icon="truck">Sin proveedores asignados.</x-empty-state>
                </div>
            @endif
        </x-card>
    </div>
</div>
</x-app-layout>
