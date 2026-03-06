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
            <form action="{{ route('inventory.store-purchase') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Producto</label>
                        <select id="product_id" name="product_id" required onchange="filterSuppliers()"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer focus:border-blue-500 focus:outline-none">
                            <option value="">Selecciona un producto</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} (Stock: {{ $product->stock }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Proveedor</label>
                        <select id="supplier_id" name="supplier_id" required disabled
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer focus:border-blue-500 focus:outline-none disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="">Selecciona primero un producto</option>
                        </select>
                        <p id="supplier-help" class="text-xs text-slate-400 mt-1">Selecciona un producto para ver sus proveedores.</p>
                        @error('supplier_id')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-input label="Cantidad a Ingresar" name="quantity" type="number" required placeholder="Ej. 10" />
                    <x-input label="Precio de Compra Unitario" name="unit_price" type="number" step="0.01" required placeholder="0.00" />
                    <div class="md:col-span-2">
                        <x-input label="Referencia (Factura / Nota de Remisión)" name="reference" placeholder="Ej. FAC-123" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input label="Notas adicionales" name="notes" placeholder="Opcional..." />
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Registrar Entrada de Stock
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
