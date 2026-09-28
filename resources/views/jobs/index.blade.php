<x-app-layout>
<div x-data="{ showJobModal: {{ $errors->any() && old('job_type_id') ? 'true' : 'false' }} }">
    <x-page-header title="Gestión de Trabajos" subtitle="Vista global de todas las tareas y su estado actual en el taller.">
        <x-slot name="actions">
            <x-button variant="primary" x-on:click="showJobModal = true">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Trabajo
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('jobs.index') }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ !request('status') ? 'bg-slate-900 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            Todos
        </a>
        <a href="{{ route('jobs.index', ['status' => 'pending']) }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'pending' ? 'bg-amber-500 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            Pendientes
        </a>
        <a href="{{ route('jobs.index', ['status' => 'in_progress']) }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'in_progress' ? 'bg-blue-500 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            En Progreso
        </a>
        <a href="{{ route('jobs.index', ['status' => 'completed']) }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'completed' ? 'bg-emerald-500 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            Completados
        </a>
    </div>

    <x-card>
        <x-table :headers="['ID', 'Trabajo / Orden', 'Mecánico', 'Vehículo', 'Costo', 'Estado', 'Acciones']">
            @forelse($jobs as $job)
                <tr>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        #T{{ str_pad($job->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-slate-900">{{ $job->jobType->name }}</div>
                        <div class="text-xs text-slate-500">
                            Orden <a href="{{ route('service-orders.show', $job->serviceOrder) }}" class="text-blue-600 hover:underline">#{{ str_pad($job->serviceOrder->id, 4, '0', STR_PAD_LEFT) }}</a>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ $job->mechanic?->name ?? 'Sin asignar' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-900 font-medium">{{ $job->vehicle_info }}</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase">{{ $job->customer_name }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        ${{ number_format($job->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $jobBadge = match($job->status) {
                                'pending' => 'amber',
                                'in_progress' => 'blue',
                                'completed' => 'emerald',
                                default => 'slate'
                            };
                        @endphp
                        <x-badge :variant="$jobBadge" class="uppercase text-[9px]">{{ $job->status_label }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('service-orders.show', $job->serviceOrder) }}" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="Ver Orden">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            @if(in_array($job->status, ['pending', 'in_progress'], true))
                            <form action="{{ route('jobs.complete', $job) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Completar">
                                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                        No hay trabajos registrados.
                    </td>
                </tr>
            @endforelse
        </x-table>
        
        <div class="mt-6">
            {{ $jobs->links() }}
        </div>
    </x-card>

    @include('jobs.partials.job-modal', [
        'action' => route('jobs.store_individual'),
        'serviceOrder' => null,
    ])
</div>
</x-app-layout>
