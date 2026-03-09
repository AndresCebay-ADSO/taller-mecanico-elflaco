<x-app-layout>
    <x-page-header title="Nueva Venta" subtitle="Registra una nueva venta de repuestos o servicios.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('sales.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-4xl" x-data="{
        items: {{ json_encode(
            collect(old('products', [['id' => '', 'quantity' => 1]]))->map(fn($p) => [
                'id' => is_array($p) ? ($p['id'] ?? '') : '',
                'quantity' => is_array($p) ? ($p['quantity'] ?? 1) : 1
            ])->values()
        ) }},
        addItem() {
            this.items.push({ id: '', quantity: 1 });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        }
    }">
        <x-card>
            <form action="{{ route('sales.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre del Cliente" name="customer_name" placeholder="Venta Mostrador" value="{{ old('customer_name') }}" />
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Método de Pago</label>
                        <select name="payment_method" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition-all">
                            <option value="Efectivo" {{ old('payment_method') == 'Efectivo' ? 'selected' : '' }}>Efectivo</option>
                            <option value="Transferencia" {{ old('payment_method') == 'Transferencia' ? 'selected' : '' }}>Transferencia</option>
                            <option value="Tarjeta" {{ old('payment_method') == 'Tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            <option value="Otro" {{ old('payment_method') == 'Otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Productos / Repuestos</h3>
                        <x-button type="button" variant="outline" @click="addItem()" class="!py-1.5 !px-3">
                            <span class="mr-1">+</span> Agregar Producto
                        </x-button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end p-4 rounded-xl border border-slate-200 bg-slate-50/50 relative group">
                                <div class="md:col-span-7">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wider">Producto</label>
                                    <select :name="`products[${index}][id]`" required
                                        :value="item.id"
                                        @change="item.id = $event.target.value"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition-all bg-white">
                                        <option value="">Seleccione un producto</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} (${{ number_format($product->sale_price, 0, ',', '.') }}) - Stock: {{ $product->stock }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wider">Cantidad</label>
                                    <input type="number" :name="`products[${index}][quantity]`" x-model="item.quantity" required min="1" 
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition-all bg-white"
                                        placeholder="1">
                                </div>
                                <div class="md:col-span-1 flex justify-center pb-1">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1" 
                                        class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                @if ($errors->any())
                <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200">
                    <ul class="list-disc list-inside text-sm text-red-600">
                        @foreach (array_unique($errors->all()) as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Registrar Venta
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

</x-app-layout>