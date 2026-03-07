<x-app-layout>
    <x-page-header title="Mecánicos" subtitle="Gestiona el equipo técnico del taller.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('mechanics.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Mecánico
            </x-button>
        </x-slot>
    </x-page-header>
    
    <x-search-filter action="{{ route('mechanics.index') }}" searchPlaceholder="Nombre del mecánico o teléfono..."></x-search-filter>

    <x-card class="overflow-hidden p-0">
        <x-table :headers="['MECÁNICO', 'TELÉFONO', 'EMAIL', 'FECHA CONTRATACIÓN', 'ESTADO', 'ACCIONES']">
            @forelse($mechanics as $mechanic)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                                {{ strtoupper(substr($mechanic->name, 0, 1)) }}
                            </div>
                            <div class="text-sm font-bold text-slate-900">{{ $mechanic->name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600">
                        {{ $mechanic->phone }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">
                        {{ $mechanic->email ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="h-3 w-3 text-slate-400"></i>
                            {{ \Carbon\Carbon::parse($mechanic->hire_date)->format('d/m/Y') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $badgeVariant = $mechanic->is_active ? 'emerald' : 'red';
                        @endphp
                        <x-badge :variant="$badgeVariant" class="px-3">
                            {{ $mechanic->is_active ? 'Activo' : 'Inactivo' }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('mechanics.edit', $mechanic) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
                            <form action="{{ route('mechanics.destroy', $mechanic) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar mecánico?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        No hay mecánicos registrados.
                    </td>
                </tr>
            @endforelse
        </x-table>
        @if($mechanics->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $mechanics->links() }}
        </div>
        @endif
    </x-card>
</x-app-layout>
