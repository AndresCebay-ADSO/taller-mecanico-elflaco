@props([
    'variant' => 'gray', // success, error, warning, blue-light, brand, gray
    'size' => 'md', // sm, md, lg
])

@php
$variantClasses = match($variant) {
    'success' => 'bg-success-50 text-success-600',
    'error' => 'bg-error-50 text-error-600',
    'warning' => 'bg-warning-50 text-warning-600',
    'brand' => 'bg-brand-50 text-brand-600',
    'blue' => 'bg-brand-50 text-brand-600',
    'emerald' => 'bg-success-50 text-success-600',
    'red' => 'bg-error-50 text-error-600',
    'amber' => 'bg-warning-50 text-warning-600',
    default => 'bg-gray-100 text-gray-700',
};

$sizeClasses = match($size) {
    'sm' => 'px-2 py-0.5 text-[10px]',
    'md' => 'px-2.5 py-0.5 text-xs',
    'lg' => 'px-3 py-1 text-sm',
    default => 'px-2.5 py-0.5 text-xs',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full font-bold tracking-tight {$variantClasses} {$sizeClasses}"]) }}>
    {{ $slot }}
</span>
