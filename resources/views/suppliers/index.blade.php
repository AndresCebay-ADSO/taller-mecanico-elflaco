<x-app-layout>
    <x-page-header title="Proveedores" subtitle="Gestiona los proveedores de repuestos y servicios.">
        <x-slot name="actions">
            <button type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-create-supplier'))"
                class="inline-flex items-center justify-center font-bold rounded-2xl transition-all duration-200 active:scale-95 cursor-pointer focus:outline-none focus:ring-4 bg-brand-500 text-white hover:bg-brand-600 focus:ring-brand-500/20 shadow-theme-sm dark:bg-brand-500 dark:hover:bg-brand-600 px-5 py-3 text-sm">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Proveedor
            </button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('suppliers.index') }}" searchPlaceholder="Buscar nombre o teléfono..." />

    <x-card>
        <x-table :headers="['NOMBRE', 'TELÉFONO', 'EMAIL', 'DIRECCIÓN', 'ACCIONES']">
            @forelse($suppliers as $supplier)
                <tr class="{{ !$supplier->active ? 'opacity-60' : '' }}">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/30 font-bold text-indigo-700 dark:text-indigo-400">
                                {{ strtoupper(substr($supplier->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    {{ $supplier->name }}
                                    @if($supplier->active)
                                        <x-badge variant="emerald" class="px-2 py-0.5 text-[10px] uppercase">Activo</x-badge>
                                    @else
                                        <x-badge variant="slate" class="px-2 py-0.5 text-[10px] uppercase">Inactivo</x-badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                        {{ $supplier->phone }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                        {{ $supplier->email ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                        {{ $supplier->address ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-edit-supplier', { detail: {
                                    id: {{ $supplier->id }},
                                    name: '{{ addslashes($supplier->name) }}',
                                    phone: '{{ $supplier->phone }}',
                                    email: '{{ $supplier->email ?? '' }}',
                                    address: '{{ addslashes($supplier->address ?? '') }}'
                                }}))"
                                class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors cursor-pointer" title="Editar">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </button>
                            <form action="{{ route('suppliers.toggleActive', $supplier) }}" method="POST" class="inline" onsubmit="return confirm('¿Desea {{ $supplier->active ? 'desactivar' : 'activar' }} este proveedor?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg p-2 {{ $supplier->active ? 'text-emerald-500 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100' }} transition-colors cursor-pointer" title="{{ $supplier->active ? 'Desactivar' : 'Activar' }}">
                                    <i data-lucide="{{ $supplier->active ? 'toggle-right' : 'toggle-left' }}" class="h-5 w-5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        No hay proveedores registrados.
                    </td>
                </tr>
            @endforelse
        </x-table>
        @if($suppliers->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $suppliers->links() }}
        </div>
        @endif
    </x-card>

    {{-- Modal Crear Proveedor --}}
    <div x-data="{ open: {{ $errors->any() && !old('_method') ? 'true' : 'false' }} }" @open-create-supplier.window="open = true">
        <template x-if="open">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
                 @click.self="open = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="scale-95 opacity-0"
                     x-transition:enter-end="scale-100 opacity-100">
                    <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Nuevo Proveedor</h3>
                        <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <form action="{{ route('suppliers.store') }}" method="POST" class="p-6 space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-2">
                            <x-input label="Nombre" name="name" required placeholder="Ej. Repuestos El Valle" maxlength="30" :value="old('name')" />
                            <x-input label="Teléfono" name="phone" required placeholder="Ej. 3001234567"
                                type="tel" minlength="10" maxlength="10" pattern="[0-9]{10}"
                                title="Ingresa exactamente 10 dígitos numéricos" :value="old('phone')" />
                            <x-input label="Email" name="email" type="email" placeholder="ejemplo@correo.com" maxlength="255" :value="old('email')" />
                            <x-input label="Dirección" name="address" placeholder="Ej. Calle 123 # 45-67" maxlength="50" :value="old('address')" />
                        </div>
                        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700">
                            <x-button variant="primary" type="submit" class="w-full md:w-auto">
                                Guardar Proveedor
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    {{-- Modal Editar Proveedor --}}
    <div x-data="{
        open: {{ $errors->any() && old('_method') === 'PUT' ? 'true' : 'false' }},
        supplier: {
            id: '{{ old('id') }}',
            name: '{{ old('name') }}',
            phone: '{{ old('phone') }}',
            email: '{{ old('email') }}',
            address: '{{ old('address') }}'
        }
    }" @open-edit-supplier.window="supplier = $event.detail; open = true">

        <template x-if="open">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
                 @click.self="open = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="scale-95 opacity-0"
                     x-transition:enter-end="scale-100 opacity-100">
                    <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Editar Proveedor</h3>
                        <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <form :action="`/suppliers/${supplier.id}`" method="POST" class="p-6 space-y-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" x-bind:value="supplier.id">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-2">
                            <x-input label="Nombre" name="name" required maxlength="30" x-bind:value="supplier.name" />
                            <x-input label="Teléfono" name="phone" required type="tel"
                                minlength="10" maxlength="10" pattern="[0-9]{10}"
                                title="Ingresa exactamente 10 dígitos numéricos"
                                x-bind:value="supplier.phone" />
                            <x-input label="Email" name="email" type="email" maxlength="255" x-bind:value="supplier.email" />
                            <x-input label="Dirección" name="address" maxlength="50" x-bind:value="supplier.address" />
                        </div>
                        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700">
                            <x-button variant="primary" type="submit" class="w-full md:w-auto">
                                Actualizar Proveedor
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
