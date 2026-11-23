<span {{ $attributes->class(['flex overflow-hidden rounded-full bg-sand-100 dark:bg-sand-800', $heightClass()]) }} aria-hidden="true">
    @foreach ($segments() as $segment)
        <span @class(['block h-full', $segment['classes']])></span>
    @endforeach
</span>
