<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
@php
    $metaDescription = filled($body) ? \Illuminate\Support\Str::limit(strip_tags($body), 160) : 'About ' . \App\Models\Setting::get('site_title');
    $metaImage = \App\Models\Setting::get('profile_cover_path') ? route('profile.cover') : null;
@endphp
@include('partials.site-head', ['pageTitle' => $title . ' · ' . \App\Models\Setting::get('site_title'), 'metaDescription' => $metaDescription, 'metaImage' => $metaImage])
@if (\App\Support\Theme::is('version-2'))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Contact-sheet redesign (version-2 only) -- no prototype exists for
           this page (see docs/contact-sheet-implementation-plan.md Section 6),
           so this is a fresh design pass using the same tokens/rails/identity
           pattern as Home and Album. Nothing here has a naturally numbered
           list, so the top rail's frame counter is intentionally dropped
           (holes only), per the plan's own guidance for pages like this. */
        body.cs-home { background: var(--cs-lightbox, #f2f4f1); color: var(--cs-ink, #15181a); font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace; }
        .cs-rail { position: fixed; left: 0; right: 0; height: 22px; z-index: 50; background: var(--cs-ink, #15181a); display: flex; align-items: center; }
        .cs-rail.top { top: 0; } .cs-rail.bottom { bottom: 0; }
        .cs-rail .cs-holes { flex: 1; align-self: stretch; background-image: repeating-radial-gradient(circle at 11px 11px, var(--cs-lightbox, #f2f4f1) 0 4px, transparent 4px 22px); background-size: 22px 22px; }

        .cs-identity { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 44px clamp(20px, 5vw, 56px) 0; margin-bottom: clamp(28px, 5vh, 48px); }
        .cs-identity .cs-mark { font-size: 13px; font-weight: 600; letter-spacing: 0.14em; color: var(--cs-ink, #15181a); }
        .cs-identity nav { display: flex; gap: 22px; }
        .cs-identity nav a { font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--cs-ink-soft, rgba(21,24,26,.62)); padding: 4px 0; }
        .cs-identity nav a:hover, .cs-identity nav a.active { color: var(--cs-grease, #a91f28); }

        .cs-page-head { padding: 0 clamp(20px, 5vw, 56px); margin-bottom: clamp(28px, 5vh, 48px); }
        .cs-page-eyebrow { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--cs-ink-soft, rgba(21,24,26,.62)); margin-bottom: 14px; display: flex; align-items: center; gap: .5rem; }
        .cs-page-head h1 { font-family: 'Anton', 'Arial Narrow', sans-serif; text-transform: uppercase; font-size: clamp(2.6rem, 7vw, 4.6rem); line-height: .95; margin: 0; }

        .cs-page-body { padding: 0 clamp(20px, 5vw, 56px) clamp(56px, 9vh, 96px); min-height: 30vh; }
        .cs-page-body p { max-width: 42rem; font-size: 14px; line-height: 1.8; color: var(--cs-ink-soft, rgba(21,24,26,.62)); }
        .cs-empty { color: var(--cs-ink-soft, rgba(21,24,26,.62)); font-size: 13px; }

        body.cs-home footer {
            padding-bottom: 34px;
            color: var(--cs-ink-faint, rgba(21, 24, 26, .38));
            border-top-color: var(--cs-rule, rgba(21, 24, 26, .16));
        }

        @media (max-width: 767.98px) {
            .cs-identity, .cs-page-head, .cs-page-body { padding-left: 20px; padding-right: 20px; }
        }
    </style>
@endif
</head>
<body @if (\App\Support\Theme::is('version-2')) class="cs-home" @endif>

    @php
        $coverPath = \App\Models\Setting::get('profile_cover_path');
        $coverPositionY = \App\Models\Setting::get('profile_cover_position_y', '50');
        $headerHeight = \App\Models\Setting::get('profile_header_height', '280');
    @endphp
    @if (\App\Support\Theme::is('version-2'))
        @php
            $navItems = [
                ['route' => 'home', 'matches' => ['home', 'albums.show'], 'label' => 'Portfolio'],
                ['route' => 'about', 'matches' => ['about'], 'label' => 'About'],
                ['route' => 'contact', 'matches' => ['contact'], 'label' => 'Contact'],
            ];
        @endphp

        <div class="cs-rail top"><div class="cs-holes"></div></div>
        <div class="cs-rail bottom"><div class="cs-holes"></div></div>

        <div class="cs-identity">
            <a class="cs-mark" href="{{ route('home') }}">{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}</a>
            <nav>
                @foreach ($navItems as $item)
                    <a class="{{ request()->routeIs(...$item['matches']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>

        <div class="cs-page-head">
            <div class="cs-page-eyebrow"><span>&#10022;</span> {{ \App\Models\Setting::get('site_title') }}</div>
            <h1>{{ $title }}</h1>
        </div>

        <div class="cs-page-body">
            @if (filled($body))
                <p>{!! nl2br(e($body)) !!}</p>
            @else
                <div class="cs-empty">
                    <p class="mb-0">This page hasn't been written yet.</p>
                </div>
            @endif
        </div>
    @else
        @include('partials.site-nav')
        <header class="profile-header"
                style="height: {{ $headerHeight }}px; @if ($coverPath) background-image: url('{{ route('profile.cover') }}'); background-position: center {{ $coverPositionY }}%; @endif">
            <div class="container">
                <h1 class="page-hero-title mb-0">{{ $title }}</h1>
            </div>
        </header>

        @include('partials.site-tabs')

        <main class="container py-4" style="min-height: 40vh;">
            @if (filled($body))
                <p class="text-body-secondary" style="max-width: 45rem;">{!! nl2br(e($body)) !!}</p>
            @else
                <div class="empty-state">
                    <p class="mb-0">This page hasn't been written yet.</p>
                </div>
            @endif
        </main>
    @endif

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
