{{-- Campo con etiqueta, ayuda y error (DESIGN.md §8). `is-money` formatea con puntos de miles mientras se escribe. --}}
@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => '',
    'required' => false,
    'placeholder' => '',
    'isMoney' => false,
    'help' => null,
])

@php
    $finalType = $isMoney ? 'text' : $type;
    $hasError = $errors->has($name);
@endphp

<div class="ds space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-foreground">
            {{ $label }}@if($required)<span class="text-destructive-text"> *</span>@endif
        </label>
    @endif

    <input
        type="{{ $finalType }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value ?? '') }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($isMoney) inputmode="numeric" @endif
        @if($hasError) aria-invalid="true" @endif
        @if($help || $hasError) aria-describedby="{{ $name }}-hint" @endif
        {{ $attributes->except(['class', 'label', 'name', 'type', 'value', 'required', 'placeholder']) }}
        @class([
            'block h-10 w-full rounded-md border bg-card px-3 text-sm text-foreground shadow-xs transition-colors placeholder:text-muted-foreground focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 read-only:bg-muted read-only:text-muted-foreground',
            'money-input tabular-nums' => $isMoney,
            'border-input focus:border-ring focus:ring-ring/30' => ! $hasError,
            'border-destructive focus:border-destructive focus:ring-destructive/20' => $hasError,
            $attributes->get('class'),
        ])
    >

    @if($hasError)
        <p id="{{ $name }}-hint" class="text-xs text-destructive-text">{{ $errors->first($name) }}</p>
    @elseif($help)
        <p id="{{ $name }}-hint" class="text-xs text-muted-foreground">{{ $help }}</p>
    @endif
</div>
