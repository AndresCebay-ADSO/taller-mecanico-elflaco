{{--
    Detalle de los trabajos de una orden, agrupado: cada trabajo abre con una fila sombreada (servicio y
    mecánico) y debajo van sus líneas: la mano de obra y los repuestos que usó. Así se lee como grupos,
    no como pasos sueltos. Si el trabajo tiene repuestos, cierra con su subtotal.

    @props jobs  Trabajos no cancelados, con jobType, mechanic y jobProducts.product cargados
--}}
@props(['jobs'])

<x-document.lines>
    @foreach($jobs as $job)
        @php $jobTotal = $job->total_amount + $job->jobProducts->sum('total_price'); @endphp
        <tbody class="break-inside-avoid border-b border-border">
            <tr class="bg-muted/60">
                <td colspan="5" class="px-2 py-1.5">
                    <span class="font-semibold">{{ $job->jobType?->name ?? 'Servicio' }}</span>
                    <span class="text-xs text-muted-foreground">· Mecánico: {{ $job->mechanic?->name ?? 'Sin asignar' }}</span>
                </td>
            </tr>
            <x-document.line :quantity="1" unit="Servicio" :price="$job->total_amount" :total="$job->total_amount" indent>
                Mano de obra
                @if($job->description)
                    <span class="text-muted-foreground">— {{ $job->description }}</span>
                @endif
            </x-document.line>
            @foreach($job->jobProducts as $part)
                <x-document.line :quantity="$part->quantity" unit="Und." :price="$part->unit_price" :total="$part->total_price" indent>
                    {{ $part->product?->name ?? 'Producto eliminado' }}
                </x-document.line>
            @endforeach
            @if($job->jobProducts->isNotEmpty())
                <tr>
                    <td colspan="4" class="px-2 pb-2 pt-0 text-right text-xs text-muted-foreground">Subtotal del trabajo</td>
                    <td class="whitespace-nowrap px-2 pb-2 pt-0 text-right text-xs font-medium tabular-nums">{{ money($jobTotal) }}</td>
                </tr>
            @endif
        </tbody>
    @endforeach
</x-document.lines>
