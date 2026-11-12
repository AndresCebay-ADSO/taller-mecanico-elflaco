<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Nueva transferencia" subtitle="El inventario cambia de sucursal cuando la destino la recibe"
                   :back="route('branch-transfers.index')" back-label="Transferencias" />

    <form action="{{ route('branch-transfers.store') }}" method="POST" class="space-y-6">
        @csrf

        <x-card title="Ruta">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-select label="Desde" name="from_branch_id" required>
                    <option value="">Selecciona la sucursal</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('from_branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </x-select>
                <x-select label="Hacia" name="to_branch_id" required>
                    <option value="">Selecciona la sucursal</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('to_branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="Producto">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <x-select label="Producto" name="product_id" required>
                        <option value="">Selecciona un producto</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} · Stock {{ $product->stock }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-input label="Cantidad" name="quantity" inputmode="numeric" pattern="[0-9]*" required placeholder="1" />
                <x-input label="Precio unitario" name="unit_price" is-money placeholder="Opcional" />
                <div class="md:col-span-2">
                    <x-input label="Referencia" name="reference" placeholder="Opcional" maxlength="255" />
                </div>
                <div class="md:col-span-3">
                    <x-textarea label="Notas" name="notes" rows="3" placeholder="Opcional" />
                </div>
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('branch-transfers.index') }}">Cancelar</x-button>
            <x-button type="submit">Crear transferencia</x-button>
        </div>
    </form>
</div>
</x-app-layout>
