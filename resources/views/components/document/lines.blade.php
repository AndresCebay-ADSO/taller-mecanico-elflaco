{{--
    Tabla de detalle del documento: Cant. · Descripción · Valor unitario · Importe.
    Las filas van en el slot, con celdas `px-2 py-2.5`. Líneas arriba y abajo del encabezado en el color del taller.
--}}
<table class="w-full text-sm">
    <thead>
        <tr class="border-y-2 border-primary text-xs font-semibold uppercase tracking-wide text-primary">
            <th class="w-14 px-2 py-2 text-center">Cant.</th>
            <th class="px-2 py-2 text-left">Descripción</th>
            <th class="w-32 px-2 py-2 text-right">Valor unit.</th>
            <th class="w-32 px-2 py-2 text-right">Importe</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-border">
        {{ $slot }}
    </tbody>
</table>
