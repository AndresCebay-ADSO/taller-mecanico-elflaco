@props(['active' => false, 'icon' => 'circle'])

@php
$classes = $active 
    ? 'flex items-center gap-3 rounded-2xl bg-brand-500 px-4 py-3.5 text-white transition-all duration-300 shadow-lg shadow-brand-500/20 group cursor-pointer'
    : 'flex items-center gap-3 rounded-2xl px-4 py-3.5 text-gray-500 transition-all duration-300 hover:bg-white/5 hover:text-white group cursor-pointer';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <i data-lucide="{{ $icon }}" class="h-5 w-5 {{ $active ? 'text-white' : 'text-gray-500 transition-colors duration-300 group-hover:text-brand-400' }}"></i>
    <span class="text-sm font-bold tracking-tight">{{ $slot }}</span>
</a>
