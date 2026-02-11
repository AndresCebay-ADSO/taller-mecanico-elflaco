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
            <form action="{{ route('products.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input label="Nombre del Producto" name="name" required placeholder="Ej. Filtro de Aceite" />
                    
                    <div>
                        <label for="category" class="block text-sm font-semibold text-slate-700 space-y-1.5">Categoría <span class="text-red-500">*</span></label>
                        <select id="category" name="category" required class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                            <option value="">Selecciona una categoría</option>
                            <option value="Repuestos">Repuestos</option>
                            <option value="Aceites">Aceites</option>
                            <option value="Accesorios">Accesorios</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>

                    <div>
                        <label for="supplier_id" class="block text-sm font-semibold text-slate-700 space-y-1.5">Proveedor <span class="text-red-500">*</span></label>
                        <select id="supplier_id" name="supplier_id" required class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-blue-500 sm:text-sm cursor-pointer">
                            <option value="">Selecciona un proveedor</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-input label="Código UPC / Barcode" name="upc" required placeholder="Ej. 1234567890" />
                    
                    <x-input label="Precio de Compra" name="purchase_price" type="number" step="0.01" required placeholder="0.00" />
                    <x-input label="Precio de Venta" name="sale_price" type="number" step="0.01" required placeholder="0.00" />
                    
                    <x-input label="Stock Inicial" name="stock" type="number" required placeholder="0" />
                    <x-input label="Stock Mínimo (Alerta)" name="min_stock" type="number" required placeholder="5" />
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
