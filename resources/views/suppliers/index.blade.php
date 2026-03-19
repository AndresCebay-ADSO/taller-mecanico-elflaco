<x-app-layout>
    <x-page-header title="Proveedores" subtitle="Gestiona los proveedores de repuestos y servicios.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('suppliers.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Proveedor
            </x-button>
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
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors cursor-pointer">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
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
</x-app-layout>
