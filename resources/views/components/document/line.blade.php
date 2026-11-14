{{-- Una línea del detalle. `indent` la mete dentro de un grupo (trabajo). La descripción va en el slot. --}}
@props(['quantity', 'unit', 'price', 'total', 'indent' => false])

<tr>
    <td @class(['px-2 py-2', 'pl-6' => $indent])>{{ $slot }}</td>
    <td class="px-2 py-2 text-right tabular-nums">{{ $quantity }}</td>
    <td class="px-2 py-2 text-muted-foreground">{{ $unit }}</td>
    <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums">{{ money($price) }}</td>
    <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums">{{ money($total) }}</td>
</tr>
