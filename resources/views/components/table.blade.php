{{--
    Tabla (DESIGN.md §7). Cada encabezado es un texto o ['label' => '…', 'align' => 'right|center'].
    Las celdas de las vistas usan `px-4 py-3`; montos con `text-right tabular-nums`.
    Si es más ancha que su caja, hace scroll dentro de la caja, nunca la página.
--}}
@props([
    'headers' => [],
])

<div {{ $attributes->merge(['class' => 'ds overflow-x-auto']) }}>
    {{-- Primera y última columna alineadas con el relleno de <x-card> (px-5). --}}
    <table class="min-w-full text-sm [&_td:first-child]:pl-5 [&_td:last-child]:pr-5 [&_th:first-child]:pl-5 [&_th:last-child]:pr-5">
        <thead class="border-b border-border">
            <tr>
                @foreach($headers as $header)
                    @php
                        $label = is_array($header) ? $header['label'] : $header;
                        $align = is_array($header) ? ($header['align'] ?? 'left') : (mb_strtoupper($header) === 'ACCIONES' ? 'right' : 'left');
                    @endphp
                    <th scope="col" @class([
                        'whitespace-nowrap px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted-foreground',
                        'text-left' => $align === 'left',
                        'text-right' => $align === 'right',
                        'text-center' => $align === 'center',
                    ])>
                        @if($label === '')
                            <span class="sr-only">Acciones</span>
                        @else
                            {{ $label }}
                        @endif
                    </th>
                @endforeach
                @isset($actions)
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                @endisset
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            {{ $slot }}
        </tbody>
    </table>
</div>
