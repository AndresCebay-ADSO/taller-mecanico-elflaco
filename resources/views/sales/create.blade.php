<x-app-layout>
<div class="ds mx-auto max-w-4xl">
    <x-page-header title="Nueva venta" subtitle="Venta de mostrador de repuestos o productos" />

    @php
        $oldItems = collect(old('products', []))
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => ['id' => $item['id'] ?? '', 'quantity' => (int) ($item['quantity'] ?? 1)])
            ->values();
        $fieldClass = 'block h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground shadow-xs focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30';
    @endphp

    <form action="{{ route('sales.store') }}" method="POST" class="space-y-6"
          x-data="saleForm({ products: @js($products), items: @js($oldItems) })">
        @csrf

        <x-card title="Cliente y pago">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Cliente" name="customer_name" placeholder="Venta mostrador" maxlength="255"
                         help="Si se deja vacío, queda como «Venta mostrador»." />

                <x-select label="Método de pago" name="payment_method" required>
                    @foreach(['Efectivo', 'Nequi', 'Daviplata', 'Transferencia', 'Tarjeta', 'Otro'] as $method)
                        <option value="{{ $method }}" @selected(old('payment_method', 'Efectivo') === $method)>{{ $method }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="Productos" description="El precio sale del lote más antiguo (FIFO).">
            <x-slot name="actions">
                <x-button variant="secondary" size="sm" @click="addItem()">Agregar producto</x-button>
            </x-slot>

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid grid-cols-1 gap-3 rounded-md border border-border p-3 md:grid-cols-12 md:items-start md:border-0 md:p-0">
                        <div class="md:col-span-6">
                            <label :for="`product-${index}`" class="mb-1.5 block text-sm font-medium" :class="index > 0 && 'md:sr-only'">Producto</label>
                            <select :id="`product-${index}`" :name="`products[${index}][id]`" x-model="item.id" required
                                    class="{{ $fieldClass }} cursor-pointer pr-8">
                                <option value="">Selecciona un producto</option>
                                @foreach($products as $product)
                                    <option value="{{ $product['id'] }}">{{ $product['name'] }} — {{ money($product['price']) }} · Stock {{ $product['stock'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label :for="`quantity-${index}`" class="mb-1.5 block text-sm font-medium" :class="index > 0 && 'md:sr-only'">Cantidad</label>
                            <input :id="`quantity-${index}`" type="text" inputmode="numeric" pattern="[0-9]*"
                                   :name="`products[${index}][quantity]`" x-model.number="item.quantity" required placeholder="1"
                                   :class="exceedsStock(item) && '!border-destructive'"
                                   class="{{ $fieldClass }} tabular-nums">
                        </div>

                        <div class="md:col-span-3">
                            <span class="mb-1.5 block text-sm font-medium md:text-right" :class="index > 0 && 'md:sr-only'">Subtotal</span>
                            <p class="flex h-10 items-center text-sm font-medium tabular-nums md:justify-end" x-text="money(lineTotal(item))"></p>
                            <p x-show="exceedsStock(item)" x-cloak class="text-xs text-destructive-text md:text-right"
                               x-text="`Solo hay ${product(item.id)?.stock} en stock`"></p>
                        </div>

                        <div class="flex md:col-span-1 md:justify-end" :class="index === 0 ? 'md:pt-7' : ''">
                            <button type="button" x-on:click="removeItem(index)" x-show="items.length > 1"
                                    class="inline-flex size-10 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-destructive-text"
                                    title="Quitar producto">
                                <i data-lucide="trash-2" class="size-4"></i>
                                <span class="sr-only">Quitar producto</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </x-card>

        <x-card class="ml-auto max-w-sm">
            <div class="flex items-baseline justify-between gap-4">
                <span class="text-sm text-muted-foreground">Total a cobrar</span>
                <span class="text-2xl font-semibold tabular-nums" x-text="money(total)"></span>
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('sales.index') }}">Cancelar</x-button>
            <x-button type="submit">Registrar venta</x-button>
        </div>
    </form>
</div>
</x-app-layout>
