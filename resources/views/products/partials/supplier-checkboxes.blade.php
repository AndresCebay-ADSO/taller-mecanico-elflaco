{{--
    Casillas de proveedores del producto.
    @param \Illuminate\Support\Collection $suppliers
    @param array $selected  ids marcados
    @param bool  $model     true: enlaza con `selectedSuppliers` del x-data del formulario
--}}
@php $selected = array_map('strval', $selected); @endphp

@if($suppliers->isNotEmpty())
    <fieldset>
        <legend class="sr-only">Proveedores</legend>
        <div class="grid max-h-56 grid-cols-1 gap-1 overflow-y-auto sm:grid-cols-2 md:grid-cols-3">
            @foreach($suppliers as $supplier)
                <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-muted">
                    <input type="checkbox" name="supplier_ids[]" value="{{ $supplier->id }}"
                           @if($model ?? false) x-model="selectedSuppliers" @endif
                           @checked(in_array((string) $supplier->id, $selected, true))
                           class="size-4 cursor-pointer rounded border-input text-primary focus:ring-2 focus:ring-ring/30">
                    <span class="truncate" title="{{ $supplier->name }}">{{ $supplier->name }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
@else
    <x-empty-state icon="truck">
        Aún no hay proveedores.
        <x-slot name="action"><x-button variant="secondary" href="{{ route('suppliers.index') }}">Ir a proveedores</x-button></x-slot>
    </x-empty-state>
@endif

@error('supplier_ids')
    <p class="mt-2 text-xs text-destructive-text">{{ $message }}</p>
@enderror
