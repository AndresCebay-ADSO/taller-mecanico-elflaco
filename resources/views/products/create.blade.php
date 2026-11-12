<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Nuevo producto" subtitle="Agrega un artículo al inventario"
                   :back="route('products.index')" back-label="Inventario" />

    <form action="{{ route('products.store') }}" method="POST" class="space-y-6"
          x-data="{
              stock: @js((string) old('stock', '')),
              selectedSuppliers: @js(array_map('strval', old('supplier_ids', []))),
              allSuppliers: @js($suppliers->map(fn ($s) => ['id' => (string) $s->id, 'name' => $s->name])->values()),
              get filteredSuppliers() { return this.allSuppliers.filter(s => this.selectedSuppliers.includes(s.id)); },
          }">
        @csrf

        <x-card title="Producto">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Nombre" name="name" required placeholder="Ej.: Filtro de aceite Pulsar 180" maxlength="255" />
                <x-select label="Categoría" name="category" required>
                    <option value="">Selecciona una categoría</option>
                    @foreach(['Repuestos', 'Aceites', 'Accesorios', 'Otros'] as $category)
                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                    @endforeach
                </x-select>
                <x-input label="Código / UPC" name="upc" placeholder="Opcional" maxlength="50" />
            </div>
        </x-card>

        <x-card title="Proveedores" description="Quiénes venden este producto. Se puede marcar más de uno.">
            @include('products.partials.supplier-checkboxes', ['selected' => old('supplier_ids', []), 'model' => true])
        </x-card>

        <x-card title="Precios y stock">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Precio de compra" name="purchase_price" is-money required placeholder="0" />
                <x-input label="Precio de venta" name="sale_price" is-money required placeholder="0" />
                <x-input label="Stock inicial" name="stock" inputmode="numeric" pattern="[0-9]*" required placeholder="0" x-model="stock"
                         help="Unidades que hay hoy. Crea el primer lote con el precio de compra." />
                <x-input label="Stock mínimo" name="min_stock" inputmode="numeric" pattern="[0-9]*" required placeholder="5"
                         help="Al llegar aquí, el producto aparece en «Stock bajo»." />

                <div x-show="Number(stock) > 0" x-cloak class="md:col-span-2">
                    <x-select label="Proveedor del stock inicial" name="initial_supplier_id" x-bind:required="Number(stock) > 0"
                              help="Solo aparecen los proveedores marcados arriba.">
                        <option value="">Selecciona un proveedor</option>
                        <template x-for="supplier in filteredSuppliers" :key="supplier.id">
                            <option :value="supplier.id" x-text="supplier.name" :selected="supplier.id === @js((string) old('initial_supplier_id'))"></option>
                        </template>
                    </x-select>
                </div>
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('products.index') }}">Cancelar</x-button>
            <x-button type="submit">Guardar producto</x-button>
        </div>
    </form>
</div>
</x-app-layout>
