{{-- Texto largo con etiqueta, ayuda y error (DESIGN.md §8). El valor inicial va en `value`. --}}
@props([
    'label' => null,
    'name',
    'value' => '',
    'required' => false,
    'placeholder' => '',
    'rows' => 4,
    'help' => null,
])

@php $hasError = $errors->has($name); @endphp

<div class="ds space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-foreground">
            {{ $label }}@if($required)<span class="text-destructive-text"> *</span>@endif
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($hasError) aria-invalid="true" @endif
        @if($help || $hasError) aria-describedby="{{ $name }}-hint" @endif
        {{ $attributes->except(['class']) }}
        @class([
            'block w-full rounded-md border bg-card px-3 py-2 text-sm text-foreground shadow-xs transition-colors placeholder:text-muted-foreground focus:outline-none focus:ring-2',
            'border-input focus:border-ring focus:ring-ring/30' => ! $hasError,
            'border-destructive focus:border-destructive focus:ring-destructive/20' => $hasError,
            $attributes->get('class'),
        ])
    >{{ old($name, $value) }}</textarea>

    @if($hasError)
        <p id="{{ $name }}-hint" class="text-xs text-destructive-text">{{ $errors->first($name) }}</p>
    @elseif($help)
        <p id="{{ $name }}-hint" class="text-xs text-muted-foreground">{{ $help }}</p>
    @endif
</div>
