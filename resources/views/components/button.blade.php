@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';
    
    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 shadow-sm shadow-blue-500/20',
        'secondary' => 'bg-slate-100 text-slate-700 hover:bg-slate-200 focus:ring-slate-400',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm shadow-red-500/20',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 shadow-sm shadow-emerald-500/20',
        'outline' => 'border border-slate-300 text-slate-700 hover:bg-slate-50 focus:ring-slate-400',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus:ring-slate-400',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3.5 text-base',
    ];

    $classes = "{$baseClasses} {$variants[$variant]} {$sizes[$size]}";
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
