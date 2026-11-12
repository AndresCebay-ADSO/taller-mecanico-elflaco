<x-app-layout>
<div class="ds mx-auto max-w-2xl">
    <x-page-header title="Ajuste de inventario" subtitle="Corrige el stock por daño, pérdida o conteo físico"
                   :back="route('inventory.index')" back-label="Trazabilidad" />

    <form action="{{ route('inventory.store-adjustment') }}" method="POST" class="space-y-6"
          data-confirm="¿Aplicar el ajuste de inventario?"
          data-confirm-description="El stock cambia de inmediato y queda registrado en la trazabilidad."
          data-confirm-label="Aplicar ajuste">
        @csrf

        <x-card>
            <div class="space-y-4">
                <x-select label="Producto" name="product_id" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} · Stock actual {{ $product->stock }}</option>
                    @endforeach
                </x-select>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-input label="Cantidad" name="quantity" inputmode="numeric" pattern="-?[0-9]+" required placeholder="Ej.: -2"
                             help="Positiva suma unidades; negativa las resta." />
                    <x-select label="Motivo" name="reason" required>
                        @foreach(['adjustment' => 'Conteo físico / ajuste general', 'damage' => 'Producto dañado', 'loss' => 'Pérdida o robo'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>

                <x-textarea label="Explicación" name="notes" rows="3" placeholder="Qué pasó y quién lo revisó" />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('inventory.index') }}">Cancelar</x-button>
            <x-button type="submit">Aplicar ajuste</x-button>
        </div>
    </form>
</div>
</x-app-layout>
