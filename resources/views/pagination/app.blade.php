{{--
    Paginación de la app (DESIGN.md §7). Registrada en AppServiceProvider como vista por defecto.
    Escritorio: resumen + números. Celular: anterior, «Página X de Y» y siguiente.
--}}
@if ($paginator->hasPages())
    @php
        $link = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm tabular-nums transition-colors';
        $idle = $link . ' text-foreground hover:bg-muted';
        $disabled = $link . ' cursor-not-allowed text-muted-foreground opacity-50';
    @endphp
    <nav role="navigation" aria-label="Paginación" class="ds flex items-center justify-between gap-4 text-sm">
        <p class="hidden text-muted-foreground sm:block">
            @if ($paginator->firstItem())
                Mostrando <span class="tabular-nums">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                @if(method_exists($paginator, 'total')) de <span class="tabular-nums">{{ number_format($paginator->total(), 0, ',', '.') }}</span>@endif
            @endif
        </p>

        <div class="flex flex-1 items-center justify-between gap-1 sm:flex-none sm:justify-end">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-left" class="size-4"></i><span class="sr-only">Anterior</span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}"><i data-lucide="chevron-left" class="size-4"></i><span class="sr-only">Anterior</span></a>
            @endif

            <span class="text-muted-foreground sm:hidden">Página {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) de {{ $paginator->lastPage() }}@endif</span>

            @isset($elements)
                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="{{ $link }} text-muted-foreground" aria-disabled="true">{{ $element }}</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="{{ $link }} bg-muted font-medium text-foreground">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="{{ $idle }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>
            @endisset

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}"><i data-lucide="chevron-right" class="size-4"></i><span class="sr-only">Siguiente</span></a>
            @else
                <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-right" class="size-4"></i><span class="sr-only">Siguiente</span></span>
            @endif
        </div>
    </nav>
@endif
