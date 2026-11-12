<x-app-layout>
<div class="ds">
    <x-page-header title="Mecánicos" subtitle="Equipo técnico del taller">
        <x-slot name="actions">
            <x-button href="{{ route('mechanics.create') }}">Nuevo mecánico</x-button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('mechanics.index') }}" searchPlaceholder="Nombre o teléfono" />

    <x-card flush>
        @if($mechanics->isNotEmpty())
            <x-table :headers="['Mecánico', 'Teléfono', 'Correo', 'Ingreso', 'Estado', ['label' => '', 'align' => 'right']]">
                @foreach($mechanics as $mechanic)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="px-4 py-3">
                            <a href="{{ route('mechanics.edit', $mechanic) }}" class="font-medium text-primary hover:underline">{{ $mechanic->name }}</a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $mechanic->phone }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $mechanic->email ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-muted-foreground">{{ $mechanic->hire_date ? \Carbon\Carbon::parse($mechanic->hire_date)->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3"><x-active-badge :active="$mechanic->is_active" /></td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            @can('admin')
                                <form action="{{ route('mechanics.destroy', $mechanic) }}" method="POST"
                                      data-confirm="¿Eliminar a {{ $mechanic->name }}?"
                                      data-confirm-description="Si ya tiene trabajos registrados, queda desactivado para conservar el historial y las comisiones."
                                      data-confirm-label="Eliminar mecánico"
                                      data-confirm-destructive>
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="icon" class="hover:!text-destructive-text" title="Eliminar mecánico">
                                        <i data-lucide="trash-2" class="size-4"></i>
                                        <span class="sr-only">Eliminar a {{ $mechanic->name }}</span>
                                    </x-button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="users">
                    {{ request('search') ? 'Ningún mecánico coincide con la búsqueda.' : 'Aún no hay mecánicos registrados.' }}
                    @unless(request('search'))
                        <x-slot name="action"><x-button href="{{ route('mechanics.create') }}">Nuevo mecánico</x-button></x-slot>
                    @endunless
                </x-empty-state>
            </div>
        @endif

        @if($mechanics->hasPages())
            <x-slot name="footer">{{ $mechanics->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
