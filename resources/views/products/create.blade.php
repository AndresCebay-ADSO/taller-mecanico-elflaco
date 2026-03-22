<x-app-layout>
    <x-page-header title="Nuevo Producto" subtitle="Agrega un nuevo artículo al inventario.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('products.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-4xl">
        <x-card>
            <form action="{{ route('products.store') }}" method="POST" class="space-y-6" 
                x-data="{ 
                    stock: '{{ old('stock') }}', 
                    selectedSuppliers: [],
                    allSuppliers: [
                        @foreach($suppliers as $supplier)
                            { id: '{{ $supplier->id }}', name: '{{ $supplier->name }}' },
                        @endforeach
                    ],
                    get filteredSuppliers() {
                        return this.allSuppliers.filter(s => this.selectedSuppliers.includes(s.id));
                    }
                }"
                x-init="
                    @if(old('supplier_ids'))
                        selectedSuppliers = @json(old('supplier_ids'));
                    @endif
                ">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre del Producto" name="name" required placeholder="Ej. Filtro de Aceite" value="{{ old('name') }}" maxlength="255" />
                    
                    <div>
                        <label for="category" class="block text-sm font-semibold text-slate-700 space-y-1.5">Categoría <span class="text-red-500">*</span></label>
                        <select id="category" name="category" required class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                            <option value="">Selecciona una categoría</option>
                            <option value="Repuestos" {{ old('category') == 'Repuestos' ? 'selected' : '' }}>Repuestos</option>
                            <option value="Aceites" {{ old('category') == 'Aceites' ? 'selected' : '' }}>Aceites</option>
                            <option value="Accesorios" {{ old('category') == 'Accesorios' ? 'selected' : '' }}>Accesorios</option>
                            <option value="Otros" {{ old('category') == 'Otros' ? 'selected' : '' }}>Otros</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Proveedores <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 rounded-xl border border-slate-200 p-4 bg-slate-50 max-h-48 overflow-y-auto">
                            @foreach($suppliers as $supplier)
                                <label class="flex items-center gap-2 cursor-pointer rounded-lg px-3 py-2 hover:bg-white hover:shadow-sm transition-all text-sm text-slate-700">
                                    <input type="checkbox"
                                        name="supplier_ids[]"
                                        value="{{ $supplier->id }}"
                                        x-model="selectedSuppliers"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    <span>{{ $supplier->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('supplier_ids')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-input label="Código UPC / Barcode" name="upc" placeholder="Ej. 1234567890" value="{{ old('upc') }}" maxlength="50" />
                    
                    <x-input label="Precio de Compra" name="purchase_price" type="number" step="0.01" required placeholder="0.00" value="{{ old('purchase_price') }}" />
                    <x-input label="Precio de Venta" name="sale_price" type="number" step="0.01" required placeholder="0.00" value="{{ old('sale_price') }}" />
                    
                    <x-input label="Stock Inicial" name="stock" type="number" required placeholder="0" x-model="stock" />
                    
                    <div x-show="stock > 0" x-transition class="animate-in fade-in slide-in-from-top-2 duration-300">
                        <label for="initial_supplier_id" class="block text-sm font-semibold text-slate-700 space-y-1.5">Proveedor del stock inicial <span class="text-red-500">*</span></label>
                        <select id="initial_supplier_id" name="initial_supplier_id" :required="stock > 0" class="mt-1.5 block w-full rounded-xl border border-blue-200 bg-blue-50/30 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                            <option value="">Selecciona quién provee el stock inicial</option>
                            <template x-for="supplier in filteredSuppliers" :key="supplier.id">
                                <option :value="supplier.id" x-text="supplier.name" :selected="supplier.id == '{{ old('initial_supplier_id') }}'"></option>
                            </template>
                        </select>
                        @error('initial_supplier_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-input label="Stock Mínimo (Alerta)" name="min_stock" type="number" required placeholder="5" value="{{ old('min_stock') }}" />
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Guardar Producto
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
