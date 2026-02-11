@props(['active' => false, 'icon' => 'circle'])

@php
$classes = $active 
    ? 'flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 text-white transition-all duration-200 shadow-md shadow-blue-500/20 group cursor-pointer'
    : 'flex items-center gap-3 rounded-xl px-4 py-3 text-slate-400 transition-all duration-200 hover:bg-slate-800 hover:text-white group cursor-pointer';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <i data-lucide="{{ $icon }}" class="h-5 w-5 {{ $active ? 'text-white' : 'text-slate-400 transition-colors group-hover:text-white' }}"></i>
    <span class="text-sm font-medium">{{ $slot }}</span>
</a>
