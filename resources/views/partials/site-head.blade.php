@php
    $ogTitle = $ogTitle ?? $pageTitle;
    $metaImage = $metaImage ?? null;
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">

    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">

    @include(\App\Support\Theme::is('version-2') ? 'partials.site-styles-version-2' : 'partials.site-styles')
