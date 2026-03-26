@props([
    'variant' => 'gray', // success, error, warning, blue-light, brand, gray
    'size' => 'md', // sm, md, lg
])

@php
$variantClasses = match($variant) {
    'success' => 'bg-success-50 text-success-600 dark:bg-success-500/20 dark:text-success-400',
    'error' => 'bg-error-50 text-error-600 dark:bg-error-500/20 dark:text-error-400',
    'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/20 dark:text-warning-400',
    'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400',
    'blue' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400',
    'emerald' => 'bg-success-50 text-success-600 dark:bg-success-500/20 dark:text-success-400',
    'red' => 'bg-error-50 text-error-600 dark:bg-error-500/20 dark:text-error-400',
    'amber' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/20 dark:text-warning-400',
    'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400',
    'teal' => 'bg-teal-50 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400',
    default => 'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400',
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
