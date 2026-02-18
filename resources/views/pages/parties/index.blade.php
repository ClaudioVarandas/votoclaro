@extends('layouts.app')

<x-seo-meta
    :title="__('ui.parties.title') . ' — VotoClaro'"
    :description="__('ui.parties.description')"
/>

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-sand-900 dark:text-sand-100">
                {{ __('ui.parties.title') }}
            </h1>
            <p class="mt-2 text-sand-500 dark:text-sand-400">
                {{ __('ui.parties.description') }}
            </p>
            <div class="mt-2 flex items-center gap-4 text-xs text-sand-500 dark:text-sand-400">
                <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-republic-500"></span>{{ __('ui.position.favor') }}</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-parliament-500"></span>{{ __('ui.position.contra') }}</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-400"></span>{{ __('ui.position.abstencao') }}</span>
            </div>
        </div>

        {{-- Party grid --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($partyStats as $stats)
                <x-party-card :stats="$stats" />
            @endforeach
        </div>
    </div>
@endsection
