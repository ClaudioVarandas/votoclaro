@props(
    [
        'title' => 'Voto Claro — Transparência no Parlamento Português',
        'description' => '',
        'ogImage' => 'https://votoclaro.pt/og_image.jpg',
        'ogImageAlt' => 'Voto Claro: transparência legislativa em Portugal'
    ])

@push('seo')
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="VotoClaro">
    <meta property="og:locale" content="pt_PT">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="canonical" href="{{ url()->current() }}">

    {{ $slot }}
@endpush
