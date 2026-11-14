{{-- Línea de firma con su rótulo («Entregó», «Recibí conforme»). --}}
@props(['label', 'hint' => null])

<div>
    <div class="h-10 border-b border-foreground/60"></div>
    <p class="mt-1.5 text-xs font-medium">{{ $label }}</p>
    @if($hint)
        <p class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
</div>
