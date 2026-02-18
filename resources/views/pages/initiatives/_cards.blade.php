@foreach ($initiatives as $initiative)
    <x-initiative-card :initiative="$initiative" />
@endforeach
