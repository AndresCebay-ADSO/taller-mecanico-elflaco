<x-app-layout>
<div class="ds">
    <x-page-header title="Órdenes de servicio" subtitle="Motos que entran al taller y su estado">
        <x-slot name="actions">
            <x-button href="{{ route('service-orders.create') }}">Nueva orden</x-button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('service-orders.index') }}" searchPlaceholder="Número de orden o cliente">
        <x-select label="Estado" name="status">
            <option value="">Todos</option>
            @foreach(['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
        <x-input type="date" label="Desde" name="date_start" :value="request('date_start')" />
        <x-input type="date" label="Hasta" name="date_end" :value="request('date_end')" />
    </x-search-filter>

    <x-card flush>
        @if($serviceOrders->isNotEmpty())
            <x-table :headers="['Orden', 'Cliente', 'Vehículo', 'Estado', 'Ingreso', ['label' => '', 'align' => 'right']]">
                @foreach($serviceOrders as $order)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('service-orders.show', $order) }}" class="font-mono font-medium text-primary hover:underline">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $order->customer_name }}</p>
                            @if($order->customer_phone)
                                <p class="mt-0.5 text-xs tabular-nums text-muted-foreground">{{ $order->customer_phone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $order->vehicle_info }}</td>
                        <td class="px-4 py-3">
                            <x-status-badge :tone="$order->statusTone()">{{ $order->statusLabel() }}</x-status-badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-muted-foreground">{{ $order->created_at->format('d/m/Y h:i a') }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            <x-button variant="ghost" size="icon" href="{{ route('service-orders.edit', $order) }}" title="Editar orden">
                                <i data-lucide="pencil" class="size-4"></i>
                                <span class="sr-only">Editar la orden #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
                            </x-button>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="clipboard-list">
                    {{ request()->except('page') ? 'Ninguna orden coincide con la búsqueda.' : 'Aún no hay órdenes de servicio.' }}
                    @unless(request()->except('page'))
                        <x-slot name="action"><x-button href="{{ route('service-orders.create') }}">Nueva orden</x-button></x-slot>
                    @endunless
                </x-empty-state>
            </div>
        @endif

        @if($serviceOrders->hasPages())
            <x-slot name="footer">{{ $serviceOrders->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
