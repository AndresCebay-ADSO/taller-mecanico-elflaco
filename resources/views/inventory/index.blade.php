<x-app-layout>
    <x-page-header title="Movimientos de Inventario" subtitle="Trazabilidad completa de entradas y salidas.">
        <x-slot name="actions">
            <x-button variant="secondary" onclick="window.location.href='{{ route('inventory.adjustment') }}'">
                <i data-lucide="sliders" class="mr-2 h-4 w-4"></i>
                Ajuste Manual
            </x-button>
            <x-button variant="primary" onclick="window.location.href='{{ route('inventory.purchase') }}'">
                <i data-lucide="shopping-cart" class="mr-2 h-4 w-4"></i>
                Registrar Compra
            </x-button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('inventory.index') }}" searchPlaceholder="Buscar por producto o proveedor...">
        <div class="w-full md:w-1/4">
            <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
            <select name="type" id="type" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Todos los Tipos</option>
                <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Compra</option>
                <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Venta</option>
                <option value="job_usage" {{ request('type') == 'job_usage' ? 'selected' : '' }}>Uso en Trabajo</option>
                <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Ajuste (Manual/Pérdida)</option>
                <option value="reversal" {{ request('type') == 'reversal' ? 'selected' : '' }}>Reversión (Anulada)</option>
            </select>
        </div>
        <div class="w-full md:w-1/5">
            <x-input type="date" name="date_start" label="Desde" value="{{ request('date_start') }}" />
        </div>
        <div class="w-full md:w-1/5">
            <x-input type="date" name="date_end" label="Hasta" value="{{ request('date_end') }}" />
        </div>
    </x-search-filter>

    <x-card>
        <x-table :headers="['Fecha', 'Producto', 'Tipo', 'Cantidad', 'Precio Unit.', 'Proveedor / Ref', 'Notas']">
            @forelse($movements as $mov)
            <tr>
                <td class="px-6 py-4 text-sm text-slate-500 font-medium">
                    {{ $mov->movement_date->format('d/m/Y') }}
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-bold text-slate-900">{{ $mov->product->name }}</div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase">{{ $mov->product->category }}</div>
                </td>
                <td class="px-6 py-4">
                    @php
                        $typeBadge = match($mov->movement_type) {
                            'purchase' => 'emerald',
                            'sale' => 'blue',
                            'job_usage' => 'amber',
                            'adjustment' => 'slate',
                            default => 'slate'
                        };
                    @endphp
                    <x-badge :variant="$typeBadge" class="uppercase text-[9px]">
                        {{ $mov->movement_type }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 text-sm font-black {{ $mov->quantity > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                </td>
                <td class="px-6 py-4 text-sm font-bold text-slate-600">
                    ${{ number_format($mov->unit_price, 0, ',', '.') }}
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-slate-700 font-medium">{{ $mov->supplier->name ?? $mov->reference ?? 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 text-xs text-slate-400 italic">
                    {{ $mov->notes ?? '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                    No hay movimientos registrados.
                </td>
            </tr>
            @endforelse
        </x-table>
        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    </x-card>
</x-app-layout>
