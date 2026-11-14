{{-- Indicador activo/inactivo (DESIGN.md §3): activo success, inactivo neutral. `feminine` dice «Activa». --}}
@props(['active' => true, 'feminine' => false])

<x-status-badge :tone="$active ? 'success' : 'neutral'" {{ $attributes }}>
    {{ $active ? ($feminine ? 'Activa' : 'Activo') : ($feminine ? 'Inactiva' : 'Inactivo') }}
</x-status-badge>
