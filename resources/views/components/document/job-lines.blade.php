{{--
    Detalle de los trabajos de una factura, agrupado: cada trabajo abre con una fila sombreada (servicio y
    mecánico) y debajo van sus líneas: la mano de obra y los repuestos que usó. Así se lee como grupos,
    no como pasos sueltos. Si el trabajo tiene repuestos, cierra con su subtotal.

    @props lines  Invoice::lineItems(): la copia guardada al emitir la factura (decisión 11)
--}}
@props(['lines'])

<x-document.lines>
    @foreach($lines as $line)
        @php $parts = collect($line['parts']); @endphp
        <tbody class="break-inside-avoid border-b border-border">
            <tr class="bg-muted/60">
                <td colspan="5" class="px-2 py-1.5">
                    <span class="font-semibold">{{ $line['service'] }}</span>
                    <span class="text-xs text-muted-foreground">· Mecánico: {{ $line['mechanic'] }}</span>
                </td>
            </tr>
            <x-document.line :quantity="1" unit="Servicio" :price="$line['labor']" :total="$line['labor']" indent>
                Mano de obra
                @if($line['description'])
                    <span class="text-muted-foreground">— {{ $line['description'] }}</span>
                @endif
            </x-document.line>
            @foreach($parts as $part)
                <x-document.line :quantity="$part['quantity']" unit="Und." :price="$part['unit_price']" :total="$part['total_price']" indent>
                    {{ $part['name'] }}
                </x-document.line>
            @endforeach
            @if($parts->isNotEmpty())
                <tr>
                    <td colspan="4" class="px-2 pb-2 pt-0 text-right text-xs text-muted-foreground">Subtotal del trabajo</td>
                    <td class="whitespace-nowrap px-2 pb-2 pt-0 text-right text-xs font-medium tabular-nums">{{ money($line['labor'] + $parts->sum('total_price')) }}</td>
                </tr>
            @endif
        </tbody>
    @endforeach
</x-document.lines>
