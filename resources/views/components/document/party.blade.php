{{-- Bloque «Cliente», «Vehículo»… del documento: título en el color del taller y líneas debajo. --}}
@props(['title'])

<div class="min-w-0">
    <p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ $title }}</p>
    <div class="mt-1.5 space-y-0.5">{{ $slot }}</div>
</div>
