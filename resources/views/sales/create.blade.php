<x-app-layout>
    <x-page-header title="Nueva Venta" subtitle="Registra una nueva venta de repuestos o servicios.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('sales.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    @php
        $oldItems = collect(old('products', []))
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => ['id' => $item['id'] ?? '', 'quantity' => (int) ($item['quantity'] ?? 1)])
            ->values();
    @endphp

    <div class="max-w-4xl" x-data="saleForm({ products: @js($products), items: @js($oldItems) })">

        <x-card>

            <form action="{{ route('sales.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    <x-input
                        label="Nombre del Cliente"
                        name="customer_name"
                        placeholder="Venta Mostrador"
                        value="{{ old('customer_name') }}"
                        maxlength="255"
                    />

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Método de Pago
                        </label>

                        <select name="payment_method"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition-all">

                            <option value="Efectivo" {{ old('payment_method') == 'Efectivo' ? 'selected' : '' }}>Efectivo</option>
                            <option value="Nequi" {{ old('payment_method') == 'Nequi' ? 'selected' : '' }}>Nequi</option>
                            <option value="Daviplata" {{ old('payment_method') == 'Daviplata' ? 'selected' : '' }}>Daviplata</option>
                            <option value="Transferencia" {{ old('payment_method') == 'Transferencia' ? 'selected' : '' }}>Transferencia</option>
                            <option value="Tarjeta" {{ old('payment_method') == 'Tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            <option value="Otro" {{ old('payment_method') == 'Otro' ? 'selected' : '' }}>Otro</option>

                        </select>

                        @error('payment_method')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>


                <div class="space-y-4">

                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">
                            Productos / Repuestos
                        </h3>

                        <x-button
                            type="button"
                            variant="outline"
                            @click="addItem()"
                            class="!py-1.5 !px-3">

                            <span class="mr-1">+</span> Agregar Producto
                        </x-button>
                    </div>


                    <div class="space-y-3">

                        <template x-for="(item, index) in items" :key="index">

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end p-4 rounded-xl border border-slate-200 bg-slate-50/50 relative group">

                                <!-- Producto -->
                                <div class="md:col-span-6">

                                    <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wider">
                                        Producto
                                    </label>

                                    <select
                                        :name="`products[${index}][id]`"
                                        x-model="item.id"
                                        required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition-all bg-white">

                                        <option value="">Seleccione un producto</option>

                                        @foreach($products as $product)
                                            <option value="{{ $product['id'] }}">
                                                {{ $product['name'] }} — ${{ number_format($product['price'], 0, ',', '.') }} · Stock: {{ $product['stock'] }}
                                            </option>
                                        @endforeach

                                    </select>


                                </div>


                                <!-- Cantidad -->
                                <div class="md:col-span-2">

                                    <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wider">
                                        Cantidad
                                    </label>

                                    <input
                                        type="number"
                                        :name="`products[${index}][quantity]`"
                                        x-model.number="item.quantity"
                                        required
                                        min="1"
                                        :max="product(item.id)?.stock"
                                        placeholder="1"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition-all bg-white"
                                    >


                                </div>


                                <!-- Subtotal -->
                                <div class="md:col-span-3 md:text-right">
                                    <span class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wider">Subtotal</span>
                                    <p class="py-2.5 text-sm font-black text-slate-900" x-text="money(lineTotal(item))"></p>
                                    <p x-show="exceedsStock(item)" x-cloak class="text-xs font-semibold text-red-500"
                                       x-text="`Solo hay ${product(item.id)?.stock} en stock`"></p>
                                </div>

                                <!-- Eliminar -->
                                <div class="md:col-span-1 flex justify-center pb-1">

                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="p-2 text-slate-400 hover:text-red-500 transition-colors">

                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor">

                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />

                                        </svg>

                                    </button>

                                </div>

                            </div>

                        </template>

                    </div>

                </div>


                <div class="flex flex-col gap-4 pt-4 border-t border-slate-200 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total a cobrar</p>
                        <p class="text-3xl font-black text-slate-900" x-text="money(total)"></p>
                        <p class="text-xs text-slate-400">Precios según el lote más antiguo (FIFO).</p>
                    </div>
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Registrar Venta
                    </x-button>
                </div>

            </form>

        </x-card>

    </div>
</x-app-layout>