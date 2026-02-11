<x-app-layout>
    <x-page-header title="Nueva Venta" subtitle="Registra una nueva venta de repuestos o servicios.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('sales.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('sales.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Producto / Repuesto</label>
                        <select name="product_id" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (${{ number_format($product->sale_price, 0, ',', '.') }}) - Stock: {{ $product->stock }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input label="Cantidad" name="quantity" type="number" required placeholder="1" value="1" />
                </div>

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Registrar Venta
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
