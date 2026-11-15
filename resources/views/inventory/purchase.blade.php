<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Registrar compra" subtitle="Entrada de mercancía: crea un lote nuevo con su costo"
                   :back="route('inventory.index')" back-label="Trazabilidad" />

    @php
        $productSuppliers = $products->mapWithKeys(fn ($p) => [
            (string) $p->id => $p->suppliers->map(fn ($s) => ['id' => (string) $s->id, 'name' => $s->name])->values(),
        ]);
    @endphp

    <form action="{{ route('inventory.store-purchase') }}" method="POST" class="space-y-6"
          x-data="{
              productId: @js((string) old('product_id', '')),
              supplierId: @js((string) old('supplier_id', '')),
              map: @js($productSuppliers),
              get suppliers() { return this.map[this.productId] ?? []; },
          }">
        @csrf

        <x-card title="Producto y proveedor">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-select label="Producto" name="product_id" required x-model="productId">
                    <option value="">Selecciona un producto</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} · Stock {{ $product->stock }}</option>
                    @endforeach
                </x-select>

                <div class="space-y-1.5">
                    <label for="supplier_id" class="block text-sm font-medium">Proveedor <span class="text-destructive-text">*</span></label>
                    <select id="supplier_id" name="supplier_id" required x-model="supplierId" :disabled="suppliers.length === 0"
                            class="block h-10 w-full cursor-pointer rounded-md border border-input bg-card pl-3 pr-8 text-sm text-foreground shadow-xs focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30 disabled:cursor-not-allowed disabled:opacity-60">
                        <option value="" x-text="productId ? 'Selecciona un proveedor' : 'Primero elige el producto'"></option>
                        <template x-for="supplier in suppliers" :key="supplier.id">
                            <option :value="supplier.id" x-text="supplier.name" :selected="supplier.id === supplierId"></option>
                        </template>
                    </select>
                    @error('supplier_id')
                        <p class="text-xs text-destructive-text">{{ $message }}</p>
                    @else
                        <p class="text-xs" :class="productId && suppliers.length === 0 ? 'text-destructive-text' : 'text-muted-foreground'"
                           x-text="!productId ? 'Muestra solo los proveedores del producto.' : (suppliers.length === 0 ? 'Este producto no tiene proveedores: agrégalos al editar el producto.' : '')"></p>
                    @enderror
                </div>
            </div>
        </x-card>

        <x-card title="Cantidad y precios">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-input label="Cantidad" name="quantity" inputmode="numeric" pattern="[0-9]*" required placeholder="Ej.: 10" />
                <x-input label="Costo unitario" name="unit_price" is-money required placeholder="0" />
                <x-input label="Precio de venta" name="sale_price" is-money placeholder="Opcional"
                         help="Vacío: se usa el precio actual. Si pones otro, todas las unidades del producto pasan a ese precio." />
            </div>
        </x-card>

        <x-card title="Pago">
            <div class="max-w-md">
                <x-select label="¿Se pagó con la plata de la caja de hoy?" name="cash_payment" required
                          help="Si se pagó desde la caja, queda como salida en la caja de hoy. Si es a crédito o con otra plata, no.">
                    <option value="">Elige…</option>
                    <option value="none" @selected(old('cash_payment') === 'none')>No: a crédito o con otra plata</option>
                    @foreach(\App\Support\PaymentMethod::ALL as $method)
                        <option value="{{ $method }}" @selected(old('cash_payment') === $method)>Sí, en {{ mb_strtolower($method) }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="Soporte">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Referencia" name="reference" placeholder="Ej.: factura del proveedor FAC-123" maxlength="255" />
                <x-input label="Notas" name="notes" placeholder="Opcional" />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('inventory.index') }}">Cancelar</x-button>
            <x-button type="submit">Registrar compra</x-button>
        </div>
    </form>
</div>
</x-app-layout>
