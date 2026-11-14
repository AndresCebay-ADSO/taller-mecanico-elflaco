@php
    // Tras un error de validación, el diálogo se reabre en el modo y con los datos que se enviaron.
    $initial = [
        'id' => old('id', ''),
        'name' => old('name', ''),
        'address' => old('address', ''),
        'phone' => old('phone', ''),
        'email' => old('email', ''),
    ];
@endphp

<x-app-layout>
<div class="ds" x-data="{
        open: @js($errors->any()),
        branch: @js($initial),
        get editing() { return this.branch.id !== '' && this.branch.id !== null; },
        create() { this.branch = { id: '', name: '', address: '', phone: '', email: '' }; this.open = true; },
        edit(data) { this.branch = { ...data, address: data.address ?? '', phone: data.phone ?? '', email: data.email ?? '' }; this.open = true; },
     }">
    <x-page-header title="Sucursales" subtitle="Sedes del taller. Cada una tiene su propio inventario, ventas y órdenes">
        <x-slot name="actions">
            <x-button x-on:click="create()">Nueva sucursal</x-button>
        </x-slot>
    </x-page-header>

    <x-card flush>
        @if($branches->isNotEmpty())
            <x-table :headers="['Sucursal', 'Dirección', 'Teléfono', 'Correo', 'Estado', ['label' => '', 'align' => 'right']]">
                @foreach($branches as $branch)
                    @php $data = ['id' => $branch->id, 'name' => $branch->name, 'address' => $branch->address, 'phone' => $branch->phone, 'email' => $branch->email]; @endphp
                    <tr @class(['transition-colors hover:bg-muted/50', 'text-muted-foreground' => ! $branch->is_active])>
                        <td class="px-4 py-3">
                            <button type="button" x-on:click="edit({{ \Illuminate\Support\Js::from($data) }})" class="cursor-pointer text-left font-medium text-primary hover:underline">{{ $branch->name }}</button>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $branch->address ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $branch->phone ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $branch->email ?: '—' }}</td>
                        <td class="px-4 py-3"><x-active-badge :active="$branch->is_active" feminine /></td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            <form action="{{ route('branches.toggle-active', $branch) }}" method="POST"
                                  data-confirm="¿{{ $branch->is_active ? 'Desactivar' : 'Activar' }} {{ $branch->name }}?"
                                  data-confirm-description="{{ $branch->is_active ? 'Nadie podrá trabajar en esta sede hasta reactivarla. Sus datos se conservan.' : 'Vuelve a estar disponible para los usuarios asignados.' }}"
                                  data-confirm-label="{{ $branch->is_active ? 'Desactivar' : 'Activar' }}"
                                  @if($branch->is_active) data-confirm-destructive @endif>
                                @csrf
                                <x-button type="submit" variant="secondary" size="sm">{{ $branch->is_active ? 'Desactivar' : 'Activar' }}</x-button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="building-2">Aún no hay sucursales.</x-empty-state>
            </div>
        @endif
    </x-card>

    <template x-if="open">
        <div class="ds fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="branch-title"
             x-on:keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-overlay" x-on:click="open = false"></div>
            <form method="POST" :action="editing ? '{{ url('branches') }}/' + branch.id : '{{ route('branches.store') }}'"
                  class="relative w-full max-w-lg overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg">
                @csrf
                <template x-if="editing">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="id" :value="branch.id">

                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <h2 id="branch-title" class="text-base font-semibold" x-text="editing ? 'Editar sucursal' : 'Nueva sucursal'"></h2>
                    <button type="button" x-on:click="open = false" class="-m-1 cursor-pointer rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground" title="Cerrar">
                        <i data-lucide="x" class="size-4"></i>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input label="Nombre" name="name" required maxlength="255" placeholder="Ej.: Sede Principal" x-model="branch.name" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input label="Dirección" name="address" maxlength="255" placeholder="Opcional" x-model="branch.address" />
                    </div>
                    <x-input label="Teléfono" name="phone" type="tel" maxlength="20" placeholder="Opcional" x-model="branch.phone" />
                    <x-input label="Correo" name="email" type="email" maxlength="255" placeholder="Opcional" x-model="branch.email" />
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-between">
                    <x-button variant="secondary" x-on:click="open = false">Cancelar</x-button>
                    <x-button type="submit"><span x-text="editing ? 'Guardar cambios' : 'Crear sucursal'"></span></x-button>
                </div>
            </form>
        </div>
    </template>
</div>
</x-app-layout>
