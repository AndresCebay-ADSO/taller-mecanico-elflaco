{{--
    Lista desplegable con etiqueta, ayuda y error (DESIGN.md §8). Las opciones van en el slot.
    Sin `label` sirve como control suelto (por ejemplo dentro de una fila de Alpine).
--}}
@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'help' => null,
])

@php
    $errorKey = $name ? str_replace(['[', ']'], ['.', ''], $name) : null;
    $hasError = $errorKey && $errors->has($errorKey);
    $id = $attributes->get('id', $name);
@endphp

<div class="ds space-y-1.5">
    @if($label)
        <label @if($id) for="{{ $id }}" @endif class="block text-sm font-medium text-foreground">
            {{ $label }}@if($required)<span class="text-destructive-text"> *</span>@endif
        </label>
    @endif

    <select
        @if($name) name="{{ $name }}" @endif
        @if($id) id="{{ $id }}" @endif
        @if($required) required @endif
        @if($hasError) aria-invalid="true" @endif
        {{ $attributes->except(['class', 'id']) }}
        @class([
            'block h-10 w-full cursor-pointer rounded-md border bg-card pl-3 pr-8 text-sm text-foreground shadow-xs transition-colors focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60',
            'border-input focus:border-ring focus:ring-ring/30' => ! $hasError,
            'border-destructive focus:border-destructive focus:ring-destructive/20' => $hasError,
            $attributes->get('class'),
        ])
    >
        {{ $slot }}
    </select>

    @if($hasError)
        <p class="text-xs text-destructive-text">{{ $errors->first($errorKey) }}</p>
    @elseif($help)
        <p class="text-xs text-muted-foreground">{{ $help }}</p>
    @endif
</div>
