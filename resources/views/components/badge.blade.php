@props([
    'variant' => 'slate',
])

@php
    $variants = [
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
        'red' => 'bg-red-50 text-red-700 ring-red-600/10',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/10',
        'slate' => 'bg-slate-50 text-slate-600 ring-slate-500/10',
    ];

    $classes = "inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {$variants[$variant]}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
