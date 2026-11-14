@props(['active' => false, 'icon' => 'circle'])

<a {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors cursor-pointer ' . ($active
        ? 'bg-sidebar-accent text-sidebar-active'
        : 'text-sidebar-foreground hover:bg-sidebar-accent/60 hover:text-sidebar-active')]) }}
   @if($active) aria-current="page" @endif>
    <i data-lucide="{{ $icon }}" class="size-4 shrink-0"></i>
    <span class="truncate">{{ $slot }}</span>
</a>
