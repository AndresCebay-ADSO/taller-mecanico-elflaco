<x-app-layout>
<div class="ds" x-data="{ showJobModal: {{ $errors->any() && old('job_type_id') ? 'true' : 'false' }} }">
    <x-page-header title="Trabajos" subtitle="Todas las tareas del taller y su estado">
        <x-slot name="actions">
            <x-button x-on:click="showJobModal = true">Nuevo trabajo</x-button>
        </x-slot>
    </x-page-header>

    {{-- Filtro por estado: pestañas neutras (el color es solo para las píldoras de estado). --}}
    @php
        $tabs = ['' => 'Todos', 'pending' => 'Pendientes', 'in_progress' => 'En progreso', 'completed' => 'Completados', 'cancelled' => 'Cancelados'];
        $currentStatus = (string) request('status');
    @endphp
    <nav class="mb-4 inline-flex flex-wrap gap-1 rounded-lg bg-muted p-1" aria-label="Filtrar por estado">
        @foreach($tabs as $value => $label)
            <a href="{{ route('jobs.index', $value === '' ? [] : ['status' => $value]) }}"
               @if($currentStatus === $value) aria-current="page" @endif
               @class([
                   'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                   'bg-card text-foreground shadow-xs' => $currentStatus === $value,
                   'text-muted-foreground hover:text-foreground' => $currentStatus !== $value,
               ])>{{ $label }}</a>
        @endforeach
    </nav>

    <x-card flush>
        @if($jobs->isNotEmpty())
            <x-table :headers="['Trabajo', 'Orden', 'Mecánico', 'Cliente y moto', 'Estado', ['label' => 'Mano de obra', 'align' => 'right'], ['label' => '', 'align' => 'right']]">
                @foreach($jobs as $job)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $job->jobType?->name ?? 'Servicio' }}</p>
                            <p class="mt-0.5 font-mono text-xs text-muted-foreground">#T{{ str_pad($job->id, 4, '0', STR_PAD_LEFT) }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('service-orders.show', $job->serviceOrder) }}" class="font-mono font-medium text-primary hover:underline">{{ $job->serviceOrder->displayNumber() }}</a>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $job->mechanic?->name ?? 'Sin asignar' }}</td>
                        <td class="px-4 py-3">
                            <p>{{ $job->serviceOrder->customer_name }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $job->serviceOrder->vehicle_info }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :tone="$job->statusTone()">{{ $job->statusLabel() }}</x-status-badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ money($job->total_amount) }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            @if(in_array($job->status, ['pending', 'in_progress'], true))
                                <form action="{{ route('jobs.complete', $job) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="secondary" size="sm">Completar</x-button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="wrench">
                    {{ $currentStatus !== '' ? 'No hay trabajos en este estado.' : 'Aún no hay trabajos registrados.' }}
                </x-empty-state>
            </div>
        @endif

        @if($jobs->hasPages())
            <x-slot name="footer">{{ $jobs->links() }}</x-slot>
        @endif
    </x-card>

    @include('jobs.partials.job-modal', [
        'action' => route('jobs.store_individual'),
        'serviceOrder' => null,
    ])
</div>
</x-app-layout>
