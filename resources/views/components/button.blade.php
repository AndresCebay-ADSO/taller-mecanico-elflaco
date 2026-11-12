@props([
    'variant' => 'primary', // primary, secondary, ghost, destructive
    'size' => 'md', // sm, md, icon
    'type' => 'button',
])

@php
    // Nombres heredados de pantallas aún sin migrar (DESIGN.md §9).
    $variant = match ($variant) {
        'danger' => 'destructive',
        'outline' => 'secondary',
        'success' => 'primary',
        default => $variant,
    };

    $baseClasses = 'ds inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-1 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'bg-primary text-primary-foreground shadow-xs hover:bg-primary-hover',
        'secondary' => 'border border-border bg-card text-foreground shadow-xs hover:bg-muted',
        'ghost' => 'text-muted-foreground hover:bg-muted hover:text-foreground',
        'destructive' => 'bg-destructive text-destructive-foreground shadow-xs hover:bg-destructive-hover',
    ];

    $sizes = [
        'sm' => 'h-8 px-3',
        'md' => 'h-10 px-4',
        'lg' => 'h-10 px-4',
        'icon' => 'size-9',
    ];

    $classes = "{$baseClasses} " . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($attributes->has('href'))
    {{-- Con href se comporta como enlace (un <button href> no navega). --}}
    <a {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
