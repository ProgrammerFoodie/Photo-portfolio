<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
@php
    $metaDescription = \App\Models\Setting::get('profile_bio') ?: 'Photography portfolio by ' . \App\Models\Setting::get('site_title');
    $metaImage = $heroPhoto
        ? route('photos.thumbnail', $heroPhoto)
        : (\App\Models\Setting::get('profile_cover_path') ? route('profile.cover') : null);
@endphp
@include('partials.site-head', ['pageTitle' => \App\Models\Setting::get('site_title'), 'metaDescription' => $metaDescription, 'metaImage' => $metaImage])
@if (\App\Support\Theme::is('version-2'))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            $services = \App\Models\Setting::homeServices();
            $processSteps = \App\Models\Setting::homeProcessSteps();
        @endphp

        <div class="cs-rail top">
            <div class="cs-counter">
                <span>FRAME</span>
                <span class="n" id="frameCounter">00</span>
                <span>/{{ str_pad((string) $albums->count(), 2, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="cs-holes"></div>
        </div>
        <div class="cs-rail bottom"><div class="cs-holes"></div></div>

        <div class="cs-identity">
            <a class="cs-mark" href="{{ route('home') }}">{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}</a>
            <nav>
                @foreach ($navItems as $item)
                    <a class="{{ request()->routeIs(...$item['matches']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>

        <main class="cs-main">
            <section class="cs-hero">
                <div class="cs-hero-text">
                    @if (\App\Models\Setting::get('profile_display_name'))
                        <div class="eyebrow"><span>&#10022;</span> {{ \App\Models\Setting::get('profile_display_name') }}</div>
                    @endif
                    <h1>{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}</h1>
                    @if (\App\Models\Setting::get('profile_bio'))
                        <p>{{ \App\Models\Setting::get('profile_bio') }}</p>
                    @endif
                    <div class="cs-hero-stats">
                        <span>{{ number_format($totalPhotos) }} Photos</span>
                        <span>{{ number_format($totalAlbums) }} Albums</span>
                    </div>
                </div>

                @if ($heroPhoto)
                    <div class="cs-hero-feature">
                        <a href="{{ route('albums.show', $heroPhoto->album) }}" class="cs-hero-feature-img cs-grain">
                            <img src="{{ route('photos.view', [$heroPhoto->album, $heroPhoto]) }}" alt="{{ $heroPhoto->album->name }}" loading="eager" fetchpriority="high">
                        </a>
                        <div class="cs-hero-feature-tag">{{ $heroPhoto->album->name }}</div>
                        <svg class="cs-grease-mark" viewBox="0 0 100 100" aria-hidden="true">
                            <ellipse cx="50" cy="50" rx="46" ry="39" pathLength="100" transform="rotate(-7 50 50)"></ellipse>
                            <ellipse cx="51" cy="49" rx="44" ry="41" pathLength="100" transform="rotate(6 50 50)"></ellipse>
                        </svg>
                    </div>
                @endif
            </section>

            <div class="cs-ticker" aria-hidden="true">
                <div class="cs-ticker-track">
                    @foreach (array_merge($services, $services) as $service)
                        <span>{{ $service }}</span>
                    @endforeach
                </div>
            </div>

            <div class="cs-sheet-head">
                <h2>The work</h2>
                <div class="roll">{{ number_format($totalAlbums) }} ALBUMS &middot; {{ number_format($totalPhotos) }} FRAMES</div>
            </div>

            @if ($albums->isEmpty())
                <div class="cs-empty">
                    <p class="mb-0">No albums to show yet.</p>
                </div>
            @else
                <div class="cs-sheet">
                    @foreach ($albums as $index => $album)
                        @php $frame = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); @endphp
                        <a href="{{ route('albums.show', $album) }}" class="cs-tile cs-grain" data-frame="{{ $frame }}">
                            <span class="cs-tile-no">{{ $frame }}</span>
                            @if ($album->cover?->thumbnail_path)
                                <img src="{{ route('photos.thumbnail', $album->cover) }}" alt="{{ $album->name }}" loading="lazy">
                            @else
                                <div class="cs-tile-placeholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                        <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                    </svg>
                                </div>
                            @endif
                            <span class="cs-tile-name">{{ $album->name }}</span>
                            <svg class="cs-grease-mark" viewBox="0 0 100 100" aria-hidden="true">
                                <ellipse cx="50" cy="50" rx="46" ry="39" pathLength="100" transform="rotate(-7 50 50)"></ellipse>
                                <ellipse cx="51" cy="49" rx="44" ry="41" pathLength="100" transform="rotate(6 50 50)"></ellipse>
                            </svg>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Services ticker, process steps, and CTA band copy are all
                 admin-editable (Settings admin panel, "Homepage (v2)" tab). --}}
            <div class="cs-process">
                @foreach ($processSteps as $index => $step)
                    <div class="cs-process-step">
                        <div class="n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                        <div>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['body'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="cs-cta-band">
                <h2>{{ \App\Models\Setting::get('home_cta_heading') }}</h2>
                <p>{{ \App\Models\Setting::get('home_cta_subtext') }}</p>
                <a href="{{ route('contact') }}">Get in touch</a>
            </div>

            <div class="cs-contact">
                <h2>Say hello</h2>
                <p>Questions about a shoot, a gallery, or just want to talk photos?</p>
                <a href="{{ route('contact') }}" class="cs-contact-link">Go to the contact page &rarr;</a>
            </div>
        </main>
    @else
        @include('partials.site-nav')
        <header class="profile-header"
                style="height: {{ $headerHeight }}px; @if ($coverPath) background-image: url('{{ route('profile.cover') }}'); background-position: center {{ $coverPositionY }}%; @endif">
            <div class="container">
                <div class="profile-handle">{{ \App\Models\Setting::get('profile_handle') }}</div>
                @if (\App\Models\Setting::get('profile_display_name'))
                    <div class="profile-display-name">{{ \App\Models\Setting::get('profile_display_name') }}</div>
                @endif
                @if (\App\Models\Setting::get('profile_bio'))
                    <p class="profile-bio mb-0">{{ \App\Models\Setting::get('profile_bio') }}</p>
                @endif

                <div class="profile-stats">
                    <div>
                        <span class="stat-num">{{ number_format($totalPhotos) }}</span>
                        <span class="stat-label">pictures captured</span>
                    </div>
                    <div>
                        <span class="stat-num">{{ number_format($totalAlbums) }}</span>
                        <span class="stat-label">albums created</span>
                    </div>
                    <div>
                        <span class="stat-num">{{ number_format($totalDownloads) }}</span>
                        <span class="stat-label">downloaded images</span>
                    </div>
                </div>
            </div>
        </header>

        @include('partials.site-tabs')

        <main class="container py-4">
            @if ($albums->isEmpty())
                <div class="empty-state">
                    <p class="mb-0">No albums to show yet.</p>
                </div>
            @else
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
                    @foreach ($albums as $album)
                        <div class="col">
                            <a href="{{ route('albums.show', $album) }}" class="album-card d-block">
                                @if ($album->cover?->thumbnail_path)
                                    <img
                                        src="{{ route('photos.thumbnail', $album->cover) }}"
                                        alt="{{ $album->name }}"
                                        class="album-thumb"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="album-thumb-placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                            <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="album-card-body">
                                    <div class="album-title">{{ $album->name }}</div>
                                    <div class="album-meta">
                                        {{ $album->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $album->photos_count) }}
                                        @if ($album->date_taken)
                                            &middot; {{ $album->date_taken->format('M Y') }}
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </main>
    @endif

    @include('partials.site-footer')

    @if (\App\Support\Theme::is('version-2'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var heroFeature = document.querySelector('.cs-hero-feature');
                if (heroFeature) {
                    setTimeout(function () { heroFeature.classList.add('is-marked'); }, 400);
                }

                var tiles = document.querySelectorAll('.cs-tile[data-frame]');
                var counterEl = document.getElementById('frameCounter');
                if (counterEl && tiles.length && 'IntersectionObserver' in window) {
                    var countObserver = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                                counterEl.textContent = entry.target.dataset.frame;
                            }
                        });
                    }, { threshold: [0.5] });
                    tiles.forEach(function (el) { countObserver.observe(el); });
                }
            });
        </script>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
