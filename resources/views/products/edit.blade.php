<x-app-layout>
    <x-page-header title="Editar Producto" subtitle="Modifica la información de {{ $product->name }}.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('products.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-4xl">
        <x-card>
            <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre del Producto" name="name" required :value="$product->name" maxlength="255" />
                    
                    <div>
                        <label for="category" class="block text-sm font-semibold text-slate-700 space-y-1.5">Categoría <span class="text-red-500">*</span></label>
                        <select id="category" name="category" required class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                            <option value="Repuestos" {{ $product->category == 'Repuestos' ? 'selected' : '' }}>Repuestos</option>
                            <option value="Aceites" {{ $product->category == 'Aceites' ? 'selected' : '' }}>Aceites</option>
                            <option value="Accesorios" {{ $product->category == 'Accesorios' ? 'selected' : '' }}>Accesorios</option>
                            <option value="Otros" {{ $product->category == 'Otros' ? 'selected' : '' }}>Otros</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Proveedores <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 rounded-xl border border-slate-200 p-4 bg-slate-50 max-h-48 overflow-y-auto">
                            @foreach($suppliers as $supplier)
                                <label class="flex items-center gap-2 cursor-pointer rounded-lg px-3 py-2 hover:bg-white hover:shadow-sm transition-all text-sm text-slate-700 {{ in_array($supplier->id, $selectedSupplierIds) ? 'bg-white shadow-sm ring-1 ring-blue-200' : '' }}">
                                    <input type="checkbox"
                                        name="supplier_ids[]"
                                        value="{{ $supplier->id }}"
                                        {{ in_array($supplier->id, $selectedSupplierIds) ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    <span>{{ $supplier->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('supplier_ids')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-input label="Código UPC / Barcode" name="upc" :value="$product->upc" maxlength="50" />
                    
                    <x-input label="Precio de Compra" name="purchase_price" type="number" step="0.01" required value="{{ old('purchase_price', (int) $product->purchase_price) }}" />
                    <x-input label="Precio de Venta" name="sale_price" type="number" step="0.01" required value="{{ old('sale_price', (int) $product->sale_price) }}" />
                    
                    <x-input label="Stock" name="stock" type="number" required :value="$product->stock" />
                    <x-input label="Stock Mínimo (Alerta)" name="min_stock" type="number" required :value="$product->min_stock" />
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Actualizar Producto
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
