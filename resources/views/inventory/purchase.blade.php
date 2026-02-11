<x-app-layout>
    <x-page-header title="Registrar Compra" subtitle="Aumenta el stock y actualiza el precio de compra.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('inventory.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('inventory.store-purchase') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Producto</label>
                        <select name="product_id" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (Actual: {{ $product->stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Proveedor</label>
                        <select name="supplier_id" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer">
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
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
