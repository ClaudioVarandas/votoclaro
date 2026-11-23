<span {{ $attributes->class(['flex h-1.5 overflow-hidden rounded-full bg-sand-100 dark:bg-sand-800']) }} aria-hidden="true">
    @foreach ($segments() as $segment)
        <span @class(['block h-full', $segment['classes']])></span>
    @endforeach
</span>
