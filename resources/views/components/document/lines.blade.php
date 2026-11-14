{{--
    Tabla de detalle del documento: Descripción · Cantidad · Unidad · Precio · Importe.
    La descripción va primero (es lo que se lee); las cifras, a la derecha y alineadas.
    Filas: <x-document.line> (y <x-document.group> para agrupar las líneas de un trabajo).
--}}
<table {{ $attributes->merge(['class' => 'w-full text-sm']) }}>
    <thead>
        <tr class="border-y-2 border-primary text-xs font-semibold uppercase tracking-wide text-primary">
            <th class="px-2 py-2 text-left">Descripción</th>
            <th class="w-20 px-2 py-2 text-right">Cantidad</th>
            <th class="w-20 px-2 py-2 text-left">Unidad</th>
            <th class="w-28 px-2 py-2 text-right">Precio</th>
            <th class="w-28 px-2 py-2 text-right">Importe</th>
        </tr>
    </thead>
    {{ $slot }}
</table>
