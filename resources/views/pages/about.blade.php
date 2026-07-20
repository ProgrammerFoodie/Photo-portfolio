<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} &middot; {{ \App\Models\Setting::get('site_title') }}</title>

    @include(\App\Support\Theme::is('version-2') ? 'partials.site-styles-version-2' : 'partials.site-styles')
</head>
<body>

    @php
        $coverPath = \App\Models\Setting::get('profile_cover_path');
        $coverPositionY = \App\Models\Setting::get('profile_cover_position_y', '50');
        $headerHeight = \App\Models\Setting::get('profile_header_height', '280');
    @endphp
    @if (\App\Support\Theme::is('version-2'))
        <header class="page-hero @if ($coverPath) has-cover @endif"
                style="height: {{ $headerHeight }}px; @if ($coverPath) background-image: url('{{ route('profile.cover') }}'); background-position: center {{ $coverPositionY }}%; @endif">
            <div class="container">
                <div class="hero-eyebrow">
                    <span>&#10022;</span> {{ \App\Models\Setting::get('site_title') }}
                </div>
                <h1>{{ $title }}</h1>
            </div>
        </header>

        @include('partials.hero-subnav', ['showLogin' => true])
    @else
        @include('partials.site-nav')
        <header class="profile-header"
                style="height: {{ $headerHeight }}px; @if ($coverPath) background-image: url('{{ route('profile.cover') }}'); background-position: center {{ $coverPositionY }}%; @endif">
            <div class="container">
                <h1 class="page-hero-title mb-0">{{ $title }}</h1>
            </div>
        </header>

        @include('partials.site-tabs')
    @endif

    <main class="container py-4" style="min-height: 40vh;">
        <p class="text-body-secondary" style="max-width: 45rem;">{!! nl2br(e($body)) !!}</p>
    </main>

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
