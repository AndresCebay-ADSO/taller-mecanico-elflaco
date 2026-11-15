<x-app-layout>
<div class="ds">
    <x-page-header title="Tipos de trabajo" subtitle="Servicios del taller y cómo se reparte la mano de obra">
        <x-slot name="actions">
            <x-button href="{{ route('job-types.create') }}">Nuevo tipo</x-button>
        </x-slot>
    </x-page-header>

    <x-card flush>
        @if($jobTypes->isNotEmpty())
            <x-table :headers="['Servicio', 'Reparto', 'Opciones', 'Estado', ['label' => '', 'align' => 'right']]">
                @foreach($jobTypes as $type)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('job-types.edit', $type) }}" class="font-medium text-primary hover:underline">{{ $type->name }}</a>
                                @if($type->is_system)
                                    <x-status-badge>Sistema</x-status-badge>
                                @endif
                            </div>
                            <p class="mt-0.5 line-clamp-1 max-w-md text-xs text-muted-foreground" title="{{ $type->description }}">{{ $type->description }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($type->calculation_type === 'percentage')
                                <p class="tabular-nums">{{ (int) $type->mechanic_percentage }} % mecánico · {{ (int) $type->workshop_percentage }} % taller</p>
                                <p class="text-xs text-muted-foreground">Porcentaje</p>
                            @else
                                <p class="tabular-nums">{{ money($type->fixed_mechanic_amount + $type->fixed_workshop_amount) }}</p>
                                <p class="text-xs tabular-nums text-muted-foreground">Fijo: {{ money($type->fixed_mechanic_amount) }} mecánico · {{ money($type->fixed_workshop_amount) }} taller</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">
                            {{ $type->allow_products ? 'Usa repuestos' : '—' }}
                        </td>
                        <td class="px-4 py-3"><x-active-badge :active="$type->is_active" /></td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            @unless($type->is_system)
                                <form action="{{ route('job-types.destroy', $type) }}" method="POST"
                                      data-confirm="¿Eliminar «{{ $type->name }}»?"
                                      data-confirm-description="Si ya tiene trabajos registrados, queda desactivado para conservar el historial."
                                      data-confirm-label="Eliminar tipo"
                                      data-confirm-destructive>
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="icon" class="hover:!text-destructive-text" title="Eliminar tipo de trabajo">
                                        <i data-lucide="trash-2" class="size-4"></i>
                                        <span class="sr-only">Eliminar {{ $type->name }}</span>
                                    </x-button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="settings-2">
                    Aún no hay tipos de trabajo.
                    <x-slot name="action"><x-button href="{{ route('job-types.create') }}">Nuevo tipo</x-button></x-slot>
                </x-empty-state>
            </div>
        @endif

        @if($jobTypes->hasPages())
            <x-slot name="footer">{{ $jobTypes->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
