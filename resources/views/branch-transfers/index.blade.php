<x-app-layout>
<div class="ds">
    <x-page-header title="Transferencias" subtitle="Repuestos que pasan de una sucursal a otra">
        <x-slot name="actions">
            <x-button href="{{ route('branch-transfers.create') }}">Nueva transferencia</x-button>
        </x-slot>
    </x-page-header>

    <form action="{{ route('branch-transfers.index') }}" method="GET" class="mb-4 rounded-lg border border-border bg-card p-3 shadow-xs">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
            <x-select label="Estado" name="status">
                <option value="">Todos</option>
                @foreach(\App\Models\BranchTransfer::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select label="Desde" name="from_branch">
                <option value="">Todas</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('from_branch') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </x-select>
            <x-select label="Hacia" name="to_branch">
                <option value="">Todas</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('to_branch') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </x-select>
            <div class="flex gap-2">
                <x-button type="submit" class="flex-1">Filtrar</x-button>
                @if(request()->except('page'))
                    <x-button variant="ghost" href="{{ route('branch-transfers.index') }}">Limpiar</x-button>
                @endif
            </div>
        </div>
    </form>

    <x-card flush>
        @if($transfers->isNotEmpty())
            <x-table :headers="['Referencia', 'Ruta', 'Producto', ['label' => 'Cant.', 'align' => 'right'], 'Estado', 'Fecha', ['label' => '', 'align' => 'right']]">
                @foreach($transfers as $transfer)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="whitespace-nowrap px-4 py-3 font-mono">{{ $transfer->reference ?: '#' . str_pad($transfer->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5">
                                {{ $transfer->fromBranch->name }}
                                <i data-lucide="arrow-right" class="size-3.5 text-muted-foreground"></i>
                                <span class="sr-only">hacia</span>
                                {{ $transfer->toBranch->name }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $transfer->product->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $transfer->quantity }}</td>
                        <td class="px-4 py-3"><x-status-badge :tone="$transfer->statusTone()">{{ $transfer->statusLabel() }}</x-status-badge></td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-muted-foreground">{{ $transfer->created_at->format('d/m/Y h:i a') }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if($transfer->status === 'pending')
                                    <form action="{{ route('branch-transfers.update-status', $transfer) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="in_transit">
                                        <x-button type="submit" variant="secondary" size="sm">Despachar</x-button>
                                    </form>
                                @elseif($transfer->status === 'in_transit')
                                    <form action="{{ route('branch-transfers.update-status', $transfer) }}" method="POST"
                                          data-confirm="¿Confirmar que llegó la transferencia?"
                                          data-confirm-description="{{ $transfer->quantity }} × {{ $transfer->product->name }} pasan al inventario de {{ $transfer->toBranch->name }}, con el costo de sus lotes."
                                          data-confirm-label="Recibir">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="completed">
                                        <x-button type="submit" variant="secondary" size="sm">Recibir</x-button>
                                    </form>
                                @endif
                                @if(in_array($transfer->status, ['pending', 'in_transit'], true))
                                    <form action="{{ route('branch-transfers.update-status', $transfer) }}" method="POST"
                                          data-confirm="¿Cancelar esta transferencia?"
                                          data-confirm-description="No se mueve inventario: el stock solo cambia de sucursal al recibirla."
                                          data-confirm-label="Cancelar transferencia"
                                          data-confirm-destructive>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="cancelled">
                                        <x-button type="submit" variant="ghost" size="icon" class="hover:!text-destructive-text" title="Cancelar transferencia">
                                            <i data-lucide="x" class="size-4"></i>
                                            <span class="sr-only">Cancelar transferencia</span>
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="arrow-left-right">
                    {{ request()->except('page') ? 'Ninguna transferencia coincide con el filtro.' : 'Aún no hay transferencias.' }}
                </x-empty-state>
            </div>
        @endif

        @if($transfers->hasPages())
            <x-slot name="footer">{{ $transfers->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
