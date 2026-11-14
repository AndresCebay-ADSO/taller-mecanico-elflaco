<x-app-layout>
<div class="ds">
    <x-page-header title="Historial de caja" subtitle="Cada día que se usó la caja, con su cuadre"
                   :back="route('cash.index')" back-label="Caja de hoy" />

    <x-card flush>
        @if($days->isNotEmpty())
            <x-table :headers="['Día', ['label' => 'Base', 'align' => 'right'], ['label' => 'Debía haber', 'align' => 'right'], ['label' => 'Se contó', 'align' => 'right'], 'Cuadre', ['label' => 'Se sacó', 'align' => 'right'], 'Cerró']">
                @foreach($days as $day)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('cash.index', $day->date->isToday() ? [] : ['date' => $day->date->toDateString()]) }}" class="font-medium text-primary hover:underline">
                                {{ ucfirst($day->date->translatedFormat('D j M Y')) }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-muted-foreground">{{ money($day->opening_cash) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $day->isClosed() ? money($day->expected_cash) : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ $day->isClosed() ? money($day->counted_cash) : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($day->isClosed())
                                <x-status-badge :tone="$day->differenceTone()">{{ $day->differenceLabel() }}</x-status-badge>
                            @else
                                <x-status-badge :tone="$day->date->isToday() ? 'neutral' : 'warning'">{{ $day->date->isToday() ? 'Abierta' : 'Sin cerrar' }}</x-status-badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-muted-foreground">{{ $day->isClosed() ? money($day->withdrawn_cash) : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                            @if($day->isClosed())
                                {{ $day->closed_at->format('h:i a') }}{{ $day->closedBy ? ' · ' . $day->closedBy->name : '' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            @if($days->hasPages())
                <div class="border-t border-border px-5 py-3">{{ $days->links() }}</div>
            @endif
        @else
            <div class="p-5">
                <x-empty-state icon="wallet">Todavía no se ha usado la caja.</x-empty-state>
            </div>
        @endif
    </x-card>
</div>
</x-app-layout>
