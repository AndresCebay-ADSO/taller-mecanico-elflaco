<x-app-layout>
    <x-page-header title="Transferencias de Sucursales" subtitle="Gestión de transferencias de inventario">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('branch-transfers.create') }}'">
                <i data-lucide="arrow-right-left" class="mr-2 h-4 w-4"></i>
                Nueva Transferencia
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        <form action="{{ route('branch-transfers.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label for="status" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Estado</label>
                <select name="status" id="status" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                    <option value="">Todos los estados</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>En Tránsito</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completada</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>
            <div>
                <label for="from_branch" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Desde</label>
                <select name="from_branch" id="from_branch" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('from_branch') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="to_branch" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Hacia</label>
                <select name="to_branch" id="to_branch" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('to_branch') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3 flex justify-end">
                <x-button variant="primary" type="submit">
                    <i data-lucide="filter" class="mr-2 h-4 w-4"></i>
                    Filtrar
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card class="overflow-hidden p-0 mt-6">
        <x-table :headers="['REFERENCIA', 'DESDE', 'HACIA', 'PRODUCTO', 'CANTIDAD', 'ESTADO', 'FECHA', 'ACCIONES']">
            @forelse($transfers as $transfer)
                <tr>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900 dark:text-white">
                        {{ $transfer->reference ?: '#' . str_pad($transfer->id, 6, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">
                        {{ $transfer->fromBranch->name }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">
                        {{ $transfer->toBranch->name }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">
                        {{ $transfer->product->name }}
                    </td>
                    <td class="px-6 py-4">
                        <x-badge variant="slate">{{ $transfer->quantity }} unidades</x-badge>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusVariant = match($transfer->status) {
                                'pending' => 'amber',
                                'in_transit' => 'blue',
                                'completed' => 'emerald',
                                'cancelled' => 'red',
                                default => 'slate'
                            };
                            $statusText = match($transfer->status) {
                                'pending' => 'Pendiente',
                                'in_transit' => 'En Tránsito',
                                'completed' => 'Completada',
                                'cancelled' => 'Cancelada',
                                default => $transfer->status
                            };
                        @endphp
                        <x-badge :variant="$statusVariant">{{ $statusText }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">
                        {{ $transfer->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            @if($transfer->status === 'pending')
                                <form action="{{ route('branch-transfers.update-status', $transfer) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="in_transit">
                                    <button type="submit" title="Marcar en tránsito" class="rounded-lg p-2 text-blue-400 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <i data-lucide="truck" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endif
                            @if($transfer->status === 'in_transit')
                                <form action="{{ route('branch-transfers.update-status', $transfer) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" title="Completar" class="rounded-lg p-2 text-emerald-400 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endif
                            @if($transfer->status === 'pending' || $transfer->status === 'in_transit')
                                <form action="{{ route('branch-transfers.update-status', $transfer) }}" method="POST" class="inline"
                                    onsubmit="return confirm('¿Cancelar esta transferencia?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" title="Cancelar" class="rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer">
                                        <i data-lucide="x-circle" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                        No hay transferencias registradas.
                    </td>
                </tr>
            @endforelse
        </x-table>
        @if($transfers->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $transfers->links() }}
        </div>
        @endif
    </x-card>

    <script>
        lucide.createIcons();
    </script>
</x-app-layout>
