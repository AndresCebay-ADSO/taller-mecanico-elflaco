{{--
    Heredado: las pantallas nuevas usan <x-status-badge :tone="…">.
    Los nombres de color viejos (emerald, amber, purple…) se traducen a los cinco tonos de DESIGN.md §3.
--}}
@props([
    'variant' => 'gray',
    'size' => 'md',
])

<x-status-badge :tone="\App\Support\Tone::fromLegacy($variant)" {{ $attributes }}>
    {{ $slot }}
</x-status-badge>
