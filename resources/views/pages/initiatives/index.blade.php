@extends('layouts.app')

<x-seo-meta
    :title="__('ui.initiatives.title') . ' — VotoClaro'"
    :description="__('ui.initiatives.description')"
/>

@section('content')
    <div x-data="initiativeFilters()" class="space-y-6">
        {{-- Header --}}
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">
                {{ __('ui.initiatives.title') }}
            </h1>
            <p class="mt-2 text-sand-500 dark:text-sand-400">
                {{ __('ui.initiatives.description') }}
            </p>
        </div>

        {{-- Filters --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-sand-200 bg-white p-4 sm:flex-row sm:items-end dark:border-sand-800 dark:bg-sand-900">
            {{-- Search --}}
            <div class="min-w-0 flex-1">
                <label for="search" class="sr-only">{{ __('ui.initiatives.search_placeholder') }}</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-sand-400 dark:text-sand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        id="search"
                        type="text"
                        x-model.debounce.400ms="search"
                        placeholder="{{ __('ui.initiatives.search_placeholder') }}"
                        class="w-full rounded-xl border border-sand-200 bg-sand-50 py-2.5 pl-10 pr-4 text-sm text-sand-900 placeholder-sand-400 transition-colors focus:border-republic-500 focus:outline-none focus:ring-1 focus:ring-republic-500 dark:border-sand-700 dark:bg-sand-950 dark:text-sand-100 dark:placeholder-sand-500 dark:focus:border-republic-400 dark:focus:ring-republic-400"
                    >
                </div>
            </div>

            {{-- Status pills --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="mr-1 text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.initiatives.filter_status') }}</span>
                <button
                    @click="status = ''"
                    :class="status === '' ? 'bg-sand-900 text-white dark:bg-sand-100 dark:text-sand-900' : 'bg-sand-100 text-sand-600 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-400 dark:hover:bg-sand-700'"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                >
                    {{ __('ui.initiatives.all_statuses') }}
                </button>
                @foreach (['approved', 'rejected', 'in_progress'] as $statusOption)
                    <button
                        @click="status = '{{ $statusOption }}'"
                        :class="status === '{{ $statusOption }}' ? 'bg-sand-900 text-white dark:bg-sand-100 dark:text-sand-900' : 'bg-sand-100 text-sand-600 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-400 dark:hover:bg-sand-700'"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                    >
                        {{ __('ui.status.' . $statusOption) }}
                    </button>
                @endforeach
            </div>

            {{-- Author pills --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="mr-1 text-xs font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.initiatives.filter_author') }}</span>
                <button
                    @click="authorType = ''"
                    :class="authorType === '' ? 'bg-sand-900 text-white dark:bg-sand-100 dark:text-sand-900' : 'bg-sand-100 text-sand-600 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-400 dark:hover:bg-sand-700'"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                >
                    {{ __('ui.initiatives.all_authors') }}
                </button>
                @foreach (['government' => __('ui.author_type.Government'), 'parliamentary_group' => __('ui.author_type.Other')] as $authorValue => $authorLabel)
                    <button
                        @click="authorType = '{{ $authorValue }}'"
                        :class="authorType === '{{ $authorValue }}' ? 'bg-sand-900 text-white dark:bg-sand-100 dark:text-sand-900' : 'bg-sand-100 text-sand-600 hover:bg-sand-200 dark:bg-sand-800 dark:text-sand-400 dark:hover:bg-sand-700'"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                    >
                        {{ $authorLabel }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Results --}}
        <div id="initiatives-grid" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @include('pages.initiatives._cards', ['initiatives' => $initiatives])
        </div>

        @if ($initiatives->isEmpty())
            <p class="py-12 text-center text-sm text-sand-500 dark:text-sand-400">
                {{ __('ui.initiatives.no_results') }}
            </p>
        @endif

        @if ($initiatives->hasMorePages())
            <div id="load-more-container" class="mt-6 text-center">
                <button
                    @click="loadMore"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 rounded-xl border border-sand-200 bg-white px-6 py-2.5 text-sm font-medium text-sand-700 transition-colors hover:bg-sand-50 disabled:opacity-50 dark:border-sand-700 dark:bg-sand-900 dark:text-sand-300 dark:hover:bg-sand-800"
                >
                    <svg x-show="loading" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('ui.initiatives.load_more') }}
                </button>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function initiativeFilters() {
        const params = new URLSearchParams(window.location.search);

        return {
            search: params.get('search') || '',
            status: params.get('status') || '',
            authorType: params.get('author_category') || '',
            page: 1,
            loading: false,
            hasMore: {{ $initiatives->hasMorePages() ? 'true' : 'false' }},

            init() {
                this.$watch('search', () => this.applyFilters());
                this.$watch('status', () => this.applyFilters());
                this.$watch('authorType', () => this.applyFilters());
            },

            buildUrl(page) {
                const params = new URLSearchParams();
                if (this.search) params.set('search', this.search);
                if (this.status) params.set('status', this.status);
                if (this.authorType) params.set('author_category', this.authorType);
                if (page > 1) params.set('page', page);
                return '{{ route("initiatives.index") }}' + (params.toString() ? '?' + params.toString() : '');
            },

            async applyFilters() {
                this.page = 1;
                this.loading = true;

                const url = this.buildUrl(1);
                history.replaceState({}, '', url);

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const html = await response.text();
                    this.hasMore = response.headers.get('X-Has-More-Pages') === 'true';

                    document.getElementById('initiatives-grid').innerHTML = html;

                    const loadMoreContainer = document.getElementById('load-more-container');
                    if (loadMoreContainer) {
                        loadMoreContainer.style.display = this.hasMore ? '' : 'none';
                    }
                } finally {
                    this.loading = false;
                }
            },

            async loadMore() {
                if (this.loading || !this.hasMore) return;

                this.page++;
                this.loading = true;

                try {
                    const response = await fetch(this.buildUrl(this.page), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const html = await response.text();
                    this.hasMore = response.headers.get('X-Has-More-Pages') === 'true';

                    document.getElementById('initiatives-grid').insertAdjacentHTML('beforeend', html);

                    if (!this.hasMore) {
                        const loadMoreContainer = document.getElementById('load-more-container');
                        if (loadMoreContainer) {
                            loadMoreContainer.style.display = 'none';
                        }
                    }
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endpush
