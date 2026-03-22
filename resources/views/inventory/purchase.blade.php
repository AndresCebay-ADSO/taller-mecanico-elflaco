<x-app-layout>
    <x-page-header title="Registrar Compra" subtitle="Aumenta el stock y actualiza el precio de compra.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('inventory.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    {{-- Pass product-supplier map to JS --}}
    <script>
        const productSuppliers = @json(
            $products->mapWithKeys(fn($p) => [
                $p->id => $p->suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()
            ])
        );

        function filterSuppliers() {
            const productId = document.getElementById('product_id').value;
            const supplierSelect = document.getElementById('supplier_id');
            const supplierHelp = document.getElementById('supplier-help');

            // Clear current options
            supplierSelect.innerHTML = '<option value="">Selecciona un proveedor</option>';
            supplierSelect.disabled = true;

            if (!productId) return;

            const suppliers = productSuppliers[productId] || [];

            if (suppliers.length === 0) {
                supplierHelp.textContent = 'Este producto no tiene proveedores asociados.';
                supplierHelp.className = 'text-xs text-red-500 mt-1';
                return;
            }

            suppliers.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                supplierSelect.appendChild(opt);
            });

            supplierSelect.disabled = false;
            supplierHelp.textContent = suppliers.length + ' proveedor(es) disponible(s) para este producto.';
            supplierHelp.className = 'text-xs text-slate-400 mt-1';
        }

        document.addEventListener('DOMContentLoaded', filterSuppliers);
    </script>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('inventory.store-purchase') }}" method="POST" class="space-y-8">
                @csrf
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Producto</label>
                        <select id="product_id" name="product_id" required onchange="filterSuppliers()"
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/40 px-5 py-4 text-sm font-bold text-slate-700 dark:text-white cursor-pointer focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all">
                            <option value="">Selecciona un producto</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} (Stock: {{ $product->stock }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Proveedor</label>
                        <select id="supplier_id" name="supplier_id" required disabled
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/40 px-5 py-4 text-sm font-bold text-slate-700 dark:text-white cursor-pointer focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Selecciona primero un producto</option>
                        </select>
                        <p id="supplier-help" class="text-[10px] text-slate-400 font-medium mt-1.5 ml-1">Selecciona un producto para ver sus proveedores.</p>
                        @error('supplier_id')
                            <p class="text-[10px] text-red-500 font-bold mt-1.5 ml-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Cantidad a Ingresar</label>
                        <x-input name="quantity" type="number" required placeholder="Ej. 10" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Precio Compra Unitario</label>
                        <x-input name="unit_price" type="number" step="0.01" required placeholder="0.00" value="{{ old('unit_price') }}" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Precio de Venta (este lote)</label>
                        <x-input name="sale_price" type="number" step="0.01" placeholder="0.00" value="{{ old('sale_price') }}" />
                        <p class="text-[10px] text-slate-400 font-medium mt-1 ml-1">Opcional — si se deja vacío, se usa el precio de venta actual del producto.</p>
                        @error('sale_price')
                            <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Referencia (Factura / Nota)</label>
                        <x-input name="reference" placeholder="Ej. FAC-123" />
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Notas adicionales</label>
                        <x-input name="notes" placeholder="Opcional..." />
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t border-slate-100 dark:border-slate-800/50">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto px-12 py-5 text-base font-black rounded-2xl shadow-lg shadow-brand-500/20 active:scale-95 transition-all">
                        Registrar Entrada de Stock
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
