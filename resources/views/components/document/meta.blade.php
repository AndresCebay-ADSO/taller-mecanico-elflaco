{{-- Fila «etiqueta — valor» en los datos del documento (columna derecha del encabezado). --}}
@props(['label'])

<dt class="text-xs font-semibold uppercase tracking-wide text-primary">{{ $label }}</dt>
<dd {{ $attributes->merge(['class' => 'text-right tabular-nums']) }}>{{ $slot }}</dd>
