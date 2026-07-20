<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\Setting::get('site_title') }}</title>

    @include(\App\Support\Theme::is('version-2') ? 'partials.site-styles-version-2' : 'partials.site-styles')
</head>
<body>

    @php
        $coverPath = \App\Models\Setting::get('profile_cover_path');
        $coverPositionY = \App\Models\Setting::get('profile_cover_position_y', '50');
        $headerHeight = \App\Models\Setting::get('profile_header_height', '280');
    @endphp

    @if (\App\Support\Theme::is('version-2'))
        <section class="hero-grid">
            <div class="hero-tile hero-headline">
                @if (\App\Models\Setting::get('profile_display_name'))
                    <div class="hero-eyebrow">
                        <span>&#10022;</span> {{ \App\Models\Setting::get('profile_display_name') }}
                    </div>
                @endif
                <h1>{{ \App\Models\Setting::get('profile_handle') }}</h1>
                @if (\App\Models\Setting::get('profile_bio'))
                    <p class="hero-tagline">{{ \App\Models\Setting::get('profile_bio') }}</p>
                @endif
                <div class="hero-meta">
                    <span>{{ number_format($totalPhotos) }} photos</span>
                    <span>&middot;</span>
                    <span>{{ number_format($totalAlbums) }} albums</span>
                </div>
            </div>
            @foreach ($heroPhotos as $photo)
                <a href="{{ route('albums.show', $photo->album) }}" class="hero-tile hero-photo">
                    <img src="{{ route('photos.thumbnail', $photo) }}" alt="" loading="lazy">
                </a>
            @endforeach
            <div class="hero-scroll-cue">
                <span>Scroll</span>
                <i class="bi bi-chevron-down"></i>
            </div>
        </section>

        @include('partials.hero-subnav', ['showLogin' => true])
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
    @endif

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

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
