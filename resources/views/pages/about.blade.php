@extends('layouts.app')

<x-seo-meta
    :title="__('ui.about.title') . ' — VotoClaro'"
    :description="__('ui.about.description')"
/>

@section('content')
    <article class="mx-auto max-w-3xl space-y-12">
        {{-- Page heading --}}
        <header>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">
                {{ __('ui.about.title') }}
            </h1>
            <p class="mt-2 text-lg text-sand-500 dark:text-sand-400">
                {{ __('ui.about.description') }}
            </p>
        </header>

        {{-- What is VotoClaro --}}
        <section>
            <h2 class="font-serif text-xl font-semibold text-sand-900 dark:text-sand-100">
                {{ __('ui.about.what_is_title') }}
            </h2>
            <p class="mt-3 leading-relaxed text-sand-700 dark:text-sand-300">
                {{ __('ui.about.what_is_body') }}
            </p>
        </section>

        {{-- How Parliament works --}}
        <section>
            <h2 class="font-serif text-xl font-semibold text-sand-900 dark:text-sand-100">
                {{ __('ui.about.parliament_title') }}
            </h2>
            <p class="mt-3 leading-relaxed text-sand-700 dark:text-sand-300">
                {{ __('ui.about.parliament_body') }}
            </p>
        </section>

        {{-- How to read the data --}}
        <section>
            <h2 class="font-serif text-xl font-semibold text-sand-900 dark:text-sand-100">
                {{ __('ui.about.reading_data_title') }}
            </h2>
            <p class="mt-3 leading-relaxed text-sand-700 dark:text-sand-300">
                {{ __('ui.about.reading_data_body') }}
            </p>
        </section>

        {{-- Data source --}}
        <section>
            <h2 class="font-serif text-xl font-semibold text-sand-900 dark:text-sand-100">
                {{ __('ui.about.data_source_title') }}
            </h2>
            <p class="mt-3 leading-relaxed text-sand-700 dark:text-sand-300">
                {{ __('ui.about.data_source_body') }}
            </p>
        </section>

        {{-- Contact --}}
        <section>
            <h2 class="font-serif text-xl font-semibold text-sand-900 dark:text-sand-100">
                {{ __('ui.about.contact_title') }}
            </h2>
            <p class="mt-3 leading-relaxed text-sand-700 dark:text-sand-300">
                {!! __('ui.about.contact_body', [
                    'github_link' => '<a href="https://github.com/ClaudioVarandas/votoclaro" target="_blank" rel="noopener noreferrer" class="font-medium text-republic-600 underline decoration-republic-300 underline-offset-2 transition-colors hover:text-republic-700 dark:text-republic-400 dark:decoration-republic-700 dark:hover:text-republic-300">GitHub</a>',
                ]) !!}
            </p>
        </section>
    </article>
@endsection
