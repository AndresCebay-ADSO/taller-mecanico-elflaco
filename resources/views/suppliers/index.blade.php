@php
    // Tras un error de validación, el diálogo se reabre en el modo y con los datos que se enviaron.
    $reopen = $errors->any();
    $initial = [
        'id' => old('id', ''),
        'name' => old('name', ''),
        'phone' => old('phone', ''),
        'email' => old('email', ''),
        'address' => old('address', ''),
    ];
@endphp

<x-app-layout>
<div class="ds" x-data="{
        open: @js($reopen),
        supplier: @js($initial),
        get editing() { return this.supplier.id !== '' && this.supplier.id !== null; },
        create() { this.supplier = { id: '', name: '', phone: '', email: '', address: '' }; this.open = true; },
        edit(data) { this.supplier = { ...data, email: data.email ?? '', address: data.address ?? '' }; this.open = true; },
     }">
    <x-page-header title="Proveedores" subtitle="A quién se le compran repuestos y productos">
        <x-slot name="actions">
            <x-button x-on:click="create()">Nuevo proveedor</x-button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('suppliers.index') }}" searchPlaceholder="Nombre o teléfono" />

    <x-card flush>
        @if($suppliers->isNotEmpty())
            <x-table :headers="['Proveedor', 'Teléfono', 'Correo', 'Dirección', 'Estado', ['label' => '', 'align' => 'right']]">
                @foreach($suppliers as $supplier)
                    @php $data = ['id' => $supplier->id, 'name' => $supplier->name, 'phone' => $supplier->phone, 'email' => $supplier->email, 'address' => $supplier->address]; @endphp
                    <tr @class(['transition-colors hover:bg-muted/50', 'text-muted-foreground' => ! $supplier->active])>
                        <td class="px-4 py-3">
                            <button type="button" x-on:click="edit({{ \Illuminate\Support\Js::from($data) }})" class="cursor-pointer text-left font-medium text-primary hover:underline">{{ $supplier->name }}</button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $supplier->phone }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $supplier->email ?: '—' }}</td>
                        <td class="max-w-56 truncate px-4 py-3 text-muted-foreground" title="{{ $supplier->address }}">{{ $supplier->address ?: '—' }}</td>
                        <td class="px-4 py-3"><x-active-badge :active="$supplier->active" /></td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            @can('admin')
                                <form action="{{ route('suppliers.toggleActive', $supplier) }}" method="POST"
                                      data-confirm="¿{{ $supplier->active ? 'Desactivar' : 'Activar' }} a {{ $supplier->name }}?"
                                      data-confirm-description="{{ $supplier->active ? 'No aparecerá al registrar compras. Su historial se conserva.' : 'Vuelve a aparecer al registrar compras.' }}"
                                      data-confirm-label="{{ $supplier->active ? 'Desactivar' : 'Activar' }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="secondary" size="sm">{{ $supplier->active ? 'Desactivar' : 'Activar' }}</x-button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="truck">
                    {{ request('search') ? 'Ningún proveedor coincide con la búsqueda.' : 'Aún no hay proveedores.' }}
                </x-empty-state>
            </div>
        @endif

        @if($suppliers->hasPages())
            <x-slot name="footer">{{ $suppliers->links() }}</x-slot>
        @endif
    </x-card>

    {{-- Un solo diálogo para crear y editar (DESIGN.md §8: formularios cortos). --}}
    <template x-if="open">
        <div class="ds fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="supplier-title"
             x-on:keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-overlay" x-on:click="open = false"></div>
            <form method="POST" :action="editing ? '{{ url('suppliers') }}/' + supplier.id : '{{ route('suppliers.store') }}'"
                  class="relative w-full max-w-lg overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg">
                @csrf
                <template x-if="editing">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="id" :value="supplier.id">

                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <h2 id="supplier-title" class="text-base font-semibold" x-text="editing ? 'Editar proveedor' : 'Nuevo proveedor'"></h2>
                    <button type="button" x-on:click="open = false" class="-m-1 cursor-pointer rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground" title="Cerrar">
                        <i data-lucide="x" class="size-4"></i>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                    <x-input label="Nombre" name="name" required maxlength="30" placeholder="Ej.: Repuestos El Valle" x-model="supplier.name" />
                    <x-input label="Teléfono" name="phone" required type="tel" inputmode="tel" minlength="10" maxlength="10" pattern="[0-9]{10}"
                             placeholder="3001234567" title="10 dígitos, sin espacios" x-model="supplier.phone" />
                    <x-input label="Correo" name="email" type="email" maxlength="255" placeholder="Opcional" x-model="supplier.email" />
                    <x-input label="Dirección" name="address" maxlength="50" placeholder="Opcional" x-model="supplier.address" />
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-between">
                    <x-button variant="secondary" x-on:click="open = false">Cancelar</x-button>
                    <x-button type="submit"><span x-text="editing ? 'Guardar cambios' : 'Guardar proveedor'"></span></x-button>
                </div>
            </form>
        </div>
    </template>
</div>
</x-app-layout>
