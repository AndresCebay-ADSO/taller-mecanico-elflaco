@php
    use App\Services\DataImport\ImportSchema;

    $sheets = array_keys(ImportSchema::SHEETS);
    $canImport = ! $report->hasErrors() && $report->countCreate() > 0;
@endphp

<x-app-layout>
<div class="ds mx-auto max-w-5xl">
    <x-page-header title="Revisión del archivo" :subtitle="$fileName . ' · se importa en ' . ($branch?->name ?? 'la sede activa')"
                   :back="route('imports.index')" back-label="Importar datos" />

    {{-- Resultado general --}}
    @if($report->hasErrors())
        <div class="mb-6 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ \App\Support\Tone::alert('danger') }}">
            <i data-lucide="circle-x" class="mt-0.5 size-4 shrink-0"></i>
            <p><span class="font-medium">{{ count($report->errors) }} {{ count($report->errors) === 1 ? 'error' : 'errores' }}: no se puede importar todavía.</span>
                Corrígelos en el Excel (la fila es la del archivo) y vuelve a subirlo. No se guardó nada.</p>
        </div>
    @elseif($canImport)
        <div class="mb-6 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ \App\Support\Tone::alert('success') }}">
            <i data-lucide="circle-check" class="mt-0.5 size-4 shrink-0"></i>
            <p><span class="font-medium">El archivo está bien.</span> Revisa el resumen y confirma para importar.</p>
        </div>
    @else
        <div class="mb-6 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ \App\Support\Tone::alert('neutral') }}">
            <i data-lucide="info" class="mt-0.5 size-4 shrink-0"></i>
            <p>No hay nada nuevo para importar: el archivo está vacío o todo ya existe en el sistema.</p>
        </div>
    @endif

    {{-- Resumen por hoja --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach($sheets as $sheet)
            <div class="rounded-lg border border-border bg-card p-4 shadow-xs">
                <p class="text-sm font-medium text-muted-foreground">{{ $sheet }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $report->countCreate($sheet) }}</p>
                <p class="text-xs text-muted-foreground">
                    nuevos @if($report->countSkip($sheet)) · {{ $report->countSkip($sheet) }} ya existen @endif
                </p>
            </div>
        @endforeach
    </div>

    <div class="space-y-6">
        @if($report->hasErrors())
            <x-card title="Errores" :description="'Fila = número de fila en el Excel'" flush>
                <x-table :headers="['Hoja', ['label' => 'Fila', 'align' => 'right'], 'Problema']">
                    @foreach($report->errors as $error)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $error['sheet'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $error['row'] }}</td>
                            <td class="px-4 py-2.5 text-destructive-text">{{ $error['message'] }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @if($report->warnings)
            <x-card title="Avisos" description="No impiden importar, pero conviene revisarlos" flush>
                <x-table :headers="['Hoja', ['label' => 'Fila', 'align' => 'right'], 'Aviso']">
                    @foreach($report->warnings as $warning)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-2.5">{{ $warning['sheet'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $warning['row'] }}</td>
                            <td class="px-4 py-2.5 text-warning-text">{{ $warning['message'] }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        {{-- Qué se va a crear: los productos con su stock, que es lo que más importa revisar --}}
        @if($report->countCreate(ImportSchema::PRODUCTS))
            <x-card title="Productos que se van a crear" flush>
                <x-table :headers="['Producto', 'Proveedor', ['label' => 'Costo', 'align' => 'right'], ['label' => 'Venta', 'align' => 'right'], ['label' => 'Stock', 'align' => 'right'], ['label' => 'Mínimo', 'align' => 'right']]">
                    @foreach($report->create[ImportSchema::PRODUCTS] as $product)
                        <tr>
                            <td class="px-4 py-2.5">
                                <p>{{ $product['name'] }}</p>
                                <p class="text-xs text-muted-foreground">{{ $product['category'] }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-muted-foreground">{{ $product['supplier'] }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ money($product['cost']) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ money($product['price']) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $product['stock'] }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $product['min_stock'] }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        @foreach([ImportSchema::SUPPLIERS, ImportSchema::MECHANICS, ImportSchema::JOB_TYPES] as $sheet)
            @if($report->countCreate($sheet))
                <x-card :title="$sheet . ' que se van a crear'">
                    <p class="text-sm">{{ collect($report->create[$sheet])->pluck('name')->join(' · ') }}</p>
                </x-card>
            @endif
        @endforeach

        @if($report->countSkip())
            <x-card title="Ya existen (se omiten)">
                <dl class="space-y-2 text-sm">
                    @foreach($report->skip as $sheet => $names)
                        <div>
                            <dt class="text-xs font-medium text-muted-foreground">{{ $sheet }}</dt>
                            <dd>{{ implode(' · ', $names) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        @endif
    </div>

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
        <form action="{{ route('imports.discard') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-button type="submit" variant="secondary" class="w-full sm:w-auto">{{ $report->hasErrors() ? 'Volver a subir' : 'Cancelar' }}</x-button>
        </form>
        @if($canImport)
            <form action="{{ route('imports.confirm') }}" method="POST"
                  data-confirm="¿Importar {{ $report->countCreate() }} {{ $report->countCreate() === 1 ? 'registro' : 'registros' }}?"
                  data-confirm-description="Se crean en {{ $branch?->name }}. El stock entra como primer lote de cada producto."
                  data-confirm-label="Importar">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-button type="submit" class="w-full sm:w-auto">Importar</x-button>
            </form>
        @endif
    </div>
</div>
</x-app-layout>
