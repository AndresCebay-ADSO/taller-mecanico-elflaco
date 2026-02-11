<x-app-layout>
    <x-page-header title="Proveedores" subtitle="Gestiona los proveedores de repuestos y servicios.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('suppliers.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Proveedor
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        <x-table :headers="['Nombre', 'Teléfono', 'Email', 'Dirección']">
            @forelse($suppliers as $supplier)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                        {{ $supplier->name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ $supplier->phone }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ $supplier->email ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ $supplier->address ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="text-blue-600 hover:text-blue-900">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('¿Estás seguro?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
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
    </x-card>
</x-app-layout>
