@props([
    'variant' => 'primary', // primary, secondary, danger, success, ghost, outline
    'size' => 'md', // sm, md, lg
    'type' => 'button',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-bold rounded-2xl transition-all duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer focus:outline-none focus:ring-4';
    
    $variants = [
        'primary' => 'bg-brand-500 text-white hover:bg-brand-600 focus:ring-brand-500/20 shadow-theme-sm dark:bg-brand-500 dark:hover:bg-brand-600',
        'secondary' => 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 focus:ring-gray-100 shadow-theme-xs dark:bg-white/5 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/10',
        'danger' => 'bg-error-500 text-white hover:bg-error-600 focus:ring-error-500/20 shadow-theme-sm',
        'success' => 'bg-success-500 text-white hover:bg-success-600 focus:ring-success-500/20 shadow-theme-sm',
        'outline' => 'border-2 border-brand-500 text-brand-600 hover:bg-brand-50 focus:ring-brand-500/20 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10',
        'ghost' => 'text-gray-600 hover:bg-gray-100 focus:ring-gray-100 dark:text-gray-400 dark:hover:bg-white/5',
    ];

    $sizes = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-5 py-3 text-sm',
        'lg' => 'px-8 py-4 text-base',
    ];

    $classes = "{$baseClasses} {$variants[$variant]} {$sizes[$size]}";
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
