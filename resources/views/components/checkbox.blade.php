{{-- Casilla con su texto al lado y ayuda debajo (DESIGN.md §8). Envía `value` (1) si está marcada; nada si no. --}}
@props([
    'name',
    'label',
    'checked' => false,
    'help' => null,
    'value' => '1',
])

@php $id = $attributes->get('id', $name); @endphp

<div class="ds flex items-start gap-3">
    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @checked($checked)
           {{ $attributes->except(['id', 'class']) }}
           class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-input text-primary focus:ring-2 focus:ring-ring/30">
    <div>
        <label for="{{ $id }}" class="cursor-pointer text-sm font-medium">{{ $label }}</label>
        @if($help)
            <p class="text-xs text-muted-foreground">{{ $help }}</p>
        @endif
        @error($name)
            <p class="text-xs text-destructive-text">{{ $message }}</p>
        @enderror
    </div>
</div>
