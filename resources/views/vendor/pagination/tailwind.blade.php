{{-- Laravel's pagination in the site's own palette, so it reads in light and dark mode alike. --}}
@php
    $item = 'inline-flex min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm font-medium leading-5 transition-colors';
    $link = $item . ' border-sand-200 bg-white text-sand-700 hover:border-republic-300 hover:text-republic-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-republic-500 dark:border-sand-700 dark:bg-sand-900 dark:text-sand-300 dark:hover:border-republic-700 dark:hover:text-republic-400';
    $current = $item . ' border-republic-600 bg-republic-600 text-white dark:border-republic-500 dark:bg-republic-500 dark:text-sand-950';
    $disabled = $item . ' cursor-not-allowed border-sand-200 bg-sand-50 text-sand-400 dark:border-sand-800 dark:bg-sand-950 dark:text-sand-600';
    $previousIcon = '<svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
    $nextIcon = '<svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="flex items-center justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }}">{!! __('pagination.previous') !!}</a>
            @endif

            <span class="text-sm text-sand-500 dark:text-sand-400">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }}">{!! __('pagination.next') !!}</a>
            @else
                <span class="{{ $disabled }}">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-4">
            <p class="text-sm leading-5 text-sand-500 dark:text-sand-400">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-medium text-sand-700 dark:text-sand-300">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="font-medium text-sand-700 dark:text-sand-300">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('of') !!}
                <span class="font-medium text-sand-700 dark:text-sand-300">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
                {!! __('results') !!}
            </p>

            <div class="flex flex-wrap items-center gap-1.5">
                @if ($paginator->onFirstPage())
                    <span class="{{ $disabled }} px-2" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">{!! $previousIcon !!}</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }} px-2" aria-label="{{ __('pagination.previous') }}">{!! $previousIcon !!}</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1 text-sm text-sand-400 dark:text-sand-600" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $current }}" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $link }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }} px-2" aria-label="{{ __('pagination.next') }}">{!! $nextIcon !!}</a>
                @else
                    <span class="{{ $disabled }} px-2" aria-disabled="true" aria-label="{{ __('pagination.next') }}">{!! $nextIcon !!}</span>
                @endif
            </div>
        </div>
    </nav>
@endif
