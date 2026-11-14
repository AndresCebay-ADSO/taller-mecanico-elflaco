<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header :title="'Editar ' . $product->name"
                   :back="route('products.show', $product)" :back-label="$product->name" />

    <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Producto">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Nombre" name="name" required :value="$product->name" maxlength="255" />
                <div>
                    <x-input label="Categoría" name="category" required maxlength="255" list="category-options" autocomplete="off"
                             :value="$product->category" placeholder="Ej.: Filtros" help="Escribe una nueva o elige una de las que ya usas." />
                    <datalist id="category-options">
                        @foreach($categories as $category)
                            <option value="{{ $category }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <x-input label="Código / UPC" name="upc" :value="$product->upc" maxlength="50" placeholder="Opcional" />
            </div>
        </x-card>

        <x-card title="Proveedores">
            @include('products.partials.supplier-checkboxes', ['selected' => old('supplier_ids', $selectedSupplierIds)])
        </x-card>

        <x-card title="Precios y stock">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Precio de compra" name="purchase_price" is-money required :value="(int) $product->purchase_price" />
                <x-input label="Precio de venta" name="sale_price" is-money required :value="(int) $product->sale_price"
                         help="Si lo cambias, las unidades que ya hay en inventario se venden al precio nuevo." />
                <div class="ds space-y-1.5">
                    <label for="current-stock" class="block text-sm font-medium">Stock actual</label>
                    <input id="current-stock" type="text" value="{{ $product->stock }}" readonly
                           class="block h-10 w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 text-sm tabular-nums text-muted-foreground">
                    <p class="text-xs text-muted-foreground">Cambia solo con compras, ventas y ajustes de inventario.</p>
                </div>
                <x-input label="Stock mínimo" name="min_stock" inputmode="numeric" pattern="[0-9]*" required :value="$product->min_stock"
                         help="Al llegar aquí, el producto aparece en «Stock bajo»." />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('products.show', $product) }}">Cancelar</x-button>
            <x-button type="submit">Guardar cambios</x-button>
        </div>
    </form>
</div>
</x-app-layout>
