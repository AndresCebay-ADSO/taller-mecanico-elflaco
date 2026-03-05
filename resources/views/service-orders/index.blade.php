<x-app-layout>
    <x-page-header title="Órdenes de Servicio" subtitle="Listado de trabajos activos y completados.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('service-orders.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nueva Orden
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        <x-table :headers="['ID', 'Cliente', 'Vehículo', 'Estado', 'Fecha Ingreso']">
            @forelse($serviceOrders as $order)
                <tr>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-900">
                        <div class="font-medium">{{ $order->customer_name }}</div>
                        <div class="text-xs text-slate-500">{{ $order->customer_phone }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ $order->vehicle_info }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $badgeVariant = match($order->status) {
                                'pending' => 'amber',
                                'in_progress' => 'blue',
                                'completed' => 'emerald',
                                'cancelled' => 'red',
                                default => 'slate'
                            };
                            $statusLabel = match($order->status) {
                                'pending' => 'Pendiente',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                                default => $order->status
                            };
                        @endphp
                        <x-badge :variant="$badgeVariant">{{ $statusLabel }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('service-orders.show', $order) }}" class="text-slate-600 hover:text-slate-900">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            <a href="{{ route('service-orders.edit', $order) }}" class="text-blue-600 hover:text-blue-900">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        No hay órdenes de servicio registradas.
                    </td>
                </tr>
            @endforelse
        </x-table>
        @if($serviceOrders->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $serviceOrders->links() }}
        </div>
        @endif
    </x-card>
</x-app-layout>
