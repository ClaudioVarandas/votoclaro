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
        </div>

        {{-- Party grid --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($partyStats as $stats)
                <x-party-card :stats="$stats" />
            @endforeach
        </div>
    </div>
@endsection
