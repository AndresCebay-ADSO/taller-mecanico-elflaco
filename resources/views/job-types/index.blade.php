<x-app-layout>
    <x-page-header title="Tipos de Trabajo" subtitle="Define los servicios estándar y sus precios base.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('job-types.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Tipo de Trabajo
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        <x-table :headers="['Servicio', 'Descripción', 'Precio Base']">
            @forelse($jobTypes as $jobType)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                        {{ $jobType->name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ Str::limit($jobType->description, 50) }}
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">
                        ${{ number_format($jobType->base_price, 2) }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('job-types.edit', $jobType) }}" class="text-blue-600 hover:text-blue-900">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                        No hay tipos de trabajo definidos.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </x-card>
</x-app-layout>
