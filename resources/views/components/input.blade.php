@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => '',
    'required' => false,
    'placeholder' => '',
])

<div class="space-y-2">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-bold text-gray-700">
            {{ $label }} @if($required) <span class="text-error-500">*</span> @endif
        </label>
    @endif

    <div class="relative">
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            {{ $attributes->except(['class', 'label', 'name', 'type', 'value', 'required', 'placeholder']) }}
            class="block w-full rounded-2xl border border-gray-300 px-4 py-3 bg-white text-gray-950 shadow-theme-xs transition-all duration-200 placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-50 sm:text-sm @error($name) border-error-500 focus:border-error-500 focus:ring-error-500/20 @enderror"
        >
    </div>

    @error($name)
        <p class="mt-1 text-sm font-medium text-error-600">{{ $message }}</p>
    @enderror
</div>