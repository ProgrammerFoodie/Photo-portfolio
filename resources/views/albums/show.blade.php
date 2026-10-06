<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
@php
    $metaDescription = $album->description ?: ($album->photos->count() . ' photos from ' . $album->name . ' — ' . \App\Models\Setting::get('site_title'));
    $metaImage = $album->cover?->thumbnail_path ? route('photos.thumbnail', $album->cover) : null;
@endphp
@include('partials.site-head', ['pageTitle' => $album->name . ' · ' . \App\Models\Setting::get('site_title'), 'metaDescription' => $metaDescription, 'metaImage' => $metaImage])
@if (\App\Support\Theme::is('version-2'))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
@endif

    <style>
        /* Contact-sheet redesign (version-2 only) -- "roll" header replacing
           the old .album-header, matching docs/contact-sheet-implementation-plan.md
           Section 4.2. Namespaced `cs-` like the home page's design system;
           tokens (--cs-ink etc.) come from site-styles-version-2.blade.php's
           shared :root block. Default-theme rules for .album-header (below)
           are untouched. */
        body.cs-home {
            background: var(--cs-lightbox, #f2f4f1);
            color: var(--cs-ink, #15181a);
            font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace;
        }

        .cs-rail { position: fixed; left: 0; right: 0; height: 22px; z-index: 50; background: var(--cs-ink, #15181a); display: flex; align-items: center; }
        .cs-rail.top { top: 0; } .cs-rail.bottom { bottom: 0; }
        .cs-rail .cs-holes { flex: 1; align-self: stretch; background-image: repeating-radial-gradient(circle at 11px 11px, var(--cs-lightbox, #f2f4f1) 0 4px, transparent 4px 22px); background-size: 22px 22px; }
        .cs-rail .cs-counter { flex-shrink: 0; padding: 0 14px; height: 100%; display: flex; align-items: center; gap: 8px; background: var(--cs-ink, #15181a); color: var(--cs-lightbox, #f2f4f1); font-size: 11px; font-weight: 600; letter-spacing: 0.08em; }
        .cs-rail .cs-counter .n { color: #e8a4a8; }

        .cs-identity { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 44px clamp(20px, 5vw, 56px) 0; margin-bottom: clamp(28px, 5vh, 48px); }
        .cs-identity .cs-mark { font-size: 13px; font-weight: 600; letter-spacing: 0.14em; color: var(--cs-ink, #15181a); }
        .cs-identity nav { display: flex; gap: 22px; }
        .cs-identity nav a { font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--cs-ink-soft, rgba(21,24,26,.62)); padding: 4px 0; }
        .cs-identity nav a:hover, .cs-identity nav a.active { color: var(--cs-grease, #a91f28); }

        .cs-roll-head { padding: 0 clamp(20px, 5vw, 56px); margin-bottom: clamp(28px, 5vh, 48px); }
        .cs-back-link { display: inline-block; font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--cs-ink-soft, rgba(21,24,26,.62)); margin-bottom: 18px; }
        .cs-back-link:hover { color: var(--cs-grease, #a91f28); }

        .cs-roll-title-row { display: flex; align-items: center; gap: 20px; }
        .cs-roll-title { flex: 1; min-width: 0; text-align: center; }
        .cs-roll-eyebrow { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--cs-ink-faint, rgba(21,24,26,.38)); margin-bottom: 10px; }
        .cs-roll-title h1 { font-family: 'Anton', 'Arial Narrow', sans-serif; text-transform: uppercase; font-size: clamp(2rem, 5.5vw, 3.6rem); line-height: 0.98; margin: 0; }
        .cs-roll-desc { color: var(--cs-ink-soft, rgba(21,24,26,.62)); font-size: 13px; max-width: 40rem; margin: 10px auto 0; }

        .cs-album-nav { flex-shrink: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--cs-rule, rgba(21,24,26,.16)); color: var(--cs-ink, #15181a); }
        .cs-album-nav svg { width: 20px; height: 20px; }
        .cs-album-nav:hover { border-color: var(--cs-grease, #a91f28); color: var(--cs-grease, #a91f28); }
        .cs-album-nav-disabled { opacity: 0.28; pointer-events: none; }

        body.cs-home footer {
            padding-bottom: 34px;
            color: var(--cs-ink-faint, rgba(21, 24, 26, .38));
            border-top-color: var(--cs-rule, rgba(21, 24, 26, .16));
        }

        @media (max-width: 767.98px) {
            .cs-identity { padding: 44px 20px 0; }
            .cs-roll-head { padding: 0 20px; }
            .cs-roll-title-row { gap: 10px; }
            .cs-album-nav { width: 32px; height: 32px; }
        }

        .toolbar {
            padding: 0 0 1.25rem;
        }

        /* Scoped to this page's toolbar only -- .btn-tinted/.btn-primary
           stay untouched everywhere else (e.g. the Contact page's submit
           button) since they're global classes. */
        .toolbar .btn {
            border-radius: 0;
            font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .toolbar .btn-tinted {
            background: transparent;
            color: var(--cs-ink, #15181a);
            border: 1px solid var(--cs-rule, rgba(21, 24, 26, .16));
        }

        .toolbar .btn-tinted:hover {
            background: transparent;
            border-color: var(--cs-grease, #a91f28);
            color: var(--cs-grease, #a91f28);
        }

        .toolbar .btn-primary {
            background: var(--cs-ink, #15181a);
            border-color: var(--cs-ink, #15181a);
        }

        .toolbar .btn-primary:hover {
            background: var(--cs-grease, #a91f28);
            border-color: var(--cs-grease, #a91f28);
        }

        .photo-grid {
            display: block;
        }

        .photo-row {
            display: flex;
            gap: 6px;
            margin-bottom: 6px;
            align-items: flex-start;
        }

        .photo-tile {
            position: relative;
            border-radius: 0;
            overflow: hidden;
            cursor: pointer;
            background-color: #e2e4e0;
        }

        .photo-tile-no {
            position: absolute;
            z-index: 2;
            top: 8px;
            left: 8px;
            font-size: 10px;
            letter-spacing: .06em;
            font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace;
            background: rgba(21, 24, 26, .72);
            color: var(--cs-lightbox, #f2f4f1);
            padding: 2px 6px;
            pointer-events: none;
        }

        .photo-tile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.2s ease;
        }

        .photo-tile:hover .photo-tile-img {
            transform: scale(1.04);
        }

        .photo-tile-checkbox {
            appearance: none;
            -webkit-appearance: none;
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 1.4rem;
            height: 1.4rem;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.85);
            background: rgba(0, 0, 0, 0.25);
            display: none;
            z-index: 3;
            cursor: pointer;
        }

        .photo-tile-checkbox:checked {
            background-color: var(--cs-grease, #a91f28);
            border-color: var(--cs-grease, #a91f28);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='white'%3E%3Cpath d='M13.485 1.929a1 1 0 0 1 .102 1.41l-7.5 8.5a1 1 0 0 1-1.464.05L1.4 8.667a1 1 0 1 1 1.4-1.428l2.42 2.372 6.83-7.74a1 1 0 0 1 1.435-.058z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 60%;
        }

        #photoGrid.select-mode .photo-tile-checkbox {
            display: block;
        }

        .photo-tile-download {
            position: absolute;
            bottom: 0.4rem;
            right: 0.4rem;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: rgba(28, 28, 30, 0.75);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            opacity: 0;
            transition: opacity 0.15s ease;
            z-index: 2;
        }

        .photo-tile:hover .photo-tile-download {
            opacity: 1;
        }

        #photoGrid.select-mode .photo-tile-download {
            display: none;
        }

        /* iOS/macOS-style fullscreen viewer: rounded window, frosted-glass
           toolbar and controls, floating circular nav buttons. Recolored to
           the contact-sheet palette (near-black + grease-red) per the plan
           doc's Section 4.3 note -- structure/behavior unchanged. */
        #lightboxModal .modal-content {
            background-color: #0a0b0a;
            border-radius: 16px;
            overflow: hidden;
        }

        #lightboxModal .modal-header {
            background-color: rgba(28, 28, 30, 0.72);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 0.85rem 1rem;
        }

        #lightboxCounter {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            padding: 0.3rem 0.75rem;
            font-size: 0.8rem;
        }

        /* Frosted-glass pill buttons, matching the toolbar's material. */
        #lightboxModal .btn-outline-light,
        #lightboxModal .btn-tinted {
            background-color: rgba(255, 255, 255, 0.1);
            border: 0;
            border-radius: 999px;
            color: var(--cs-lightbox, #f2f4f1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: background-color 0.15s ease;
        }

        #lightboxModal .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.18);
            color: var(--cs-lightbox, #f2f4f1);
        }

        #lightboxModal .btn-tinted {
            color: #e8a4a8;
        }

        #lightboxModal .btn-tinted:hover {
            background-color: rgba(255, 255, 255, 0.18);
            color: #f0bfc2;
        }

        /* Close button as a circular frosted-glass control instead of a
           plain white X. */
        #lightboxModal .btn-close {
            width: 36px;
            height: 36px;
            padding: 0;
            margin: 0;
            background: rgba(255, 255, 255, 0.1) url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23f2f4f1'%3e%3cpath d='M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854z'/%3e%3c/svg%3e") center / 14px no-repeat;
            border-radius: 50%;
            opacity: 1;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: background-color 0.15s ease;
        }

        #lightboxModal .btn-close:hover {
            background-color: rgba(255, 255, 255, 0.18);
            opacity: 1;
        }

        #lightboxImg {
            max-height: 85vh;
        }

        /* Darker than Bootstrap's default backdrop so the photo has more
           contrast against the rest of the page behind it. */
        .modal-backdrop {
            --bs-backdrop-opacity: 0.92;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 0;
            border-radius: 50%;
            color: #fff;
            opacity: 0.85;
            transition: background-color 0.15s ease, opacity 0.15s ease;
            z-index: 5;
        }

        .lightbox-nav:hover {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.18);
        }

        .lightbox-nav.lightbox-prev {
            left: 1rem;
        }

        .lightbox-nav.lightbox-next {
            right: 1rem;
        }

        /* Generous invisible tap targets -- 30% of the image width on each
           side -- so visitors don't have to hit the small arrow precisely. */
        .lightbox-tap-zone {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 30%;
            cursor: pointer;
            z-index: 3;
        }

        .lightbox-tap-prev {
            left: 0;
        }

        .lightbox-tap-next {
            right: 0;
        }
    </style>
</head>
<body @if (\App\Support\Theme::is('version-2')) class="cs-home" @endif>

    @if (\App\Support\Theme::is('version-2'))
        @php
            $navItems = [
                ['route' => 'home', 'matches' => ['home', 'albums.show'], 'label' => 'Portfolio'],
                ['route' => 'about', 'matches' => ['about'], 'label' => 'About'],
                ['route' => 'contact', 'matches' => ['contact'], 'label' => 'Contact'],
            ];
        @endphp

        <div class="cs-rail top">
            <div class="cs-counter">
                <span>FRAME</span>
                <span class="n" id="frameCounter">00</span>
                <span>/{{ str_pad((string) $album->photos->count(), 2, '0', STR_PAD_LEFT) }}</span>
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

        <div class="cs-roll-head">
            <a class="cs-back-link" href="{{ route('home') }}">&larr; All work</a>
            <div class="cs-roll-title-row">
                @if ($prevAlbum)
                    <a class="cs-album-nav" href="{{ route('albums.show', $prevAlbum) }}" aria-label="Previous album: {{ $prevAlbum->name }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    </a>
                @else
                    <span class="cs-album-nav cs-album-nav-disabled" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    </span>
                @endif

                <div class="cs-roll-title">
                    <div class="cs-roll-eyebrow">
                        {{ $album->date_taken?->format('F Y') ?? 'Undated' }}@if($album->location) &middot; {{ $album->location }} @endif
                        &middot; {{ $album->photos->count() }} {{ \Illuminate\Support\Str::plural('frame', $album->photos->count()) }}
                        @if ($album->children->isNotEmpty())
                            &middot; {{ $album->children->count() }} {{ \Illuminate\Support\Str::plural('sub-album', $album->children->count()) }}
                        @endif
                    </div>
                    <h1>{{ $album->name }}</h1>
                    @if ($album->description)
                        <p class="cs-roll-desc">{{ $album->description }}</p>
                    @endif
                </div>

                @if ($nextAlbum)
                    <a class="cs-album-nav" href="{{ route('albums.show', $nextAlbum) }}" aria-label="Next album: {{ $nextAlbum->name }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                    </a>
                @else
                    <span class="cs-album-nav cs-album-nav-disabled" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                    </span>
                @endif
            </div>
        </div>
    @else
        @include('partials.site-nav')

        <header class="album-header">
            <div class="container">
                <a href="{{ route('home') }}" class="back-link">&larr; All albums</a>
                <h1>{{ $album->name }}</h1>
                <div class="meta">
                    {{ $album->photos->count() }} {{ \Illuminate\Support\Str::plural('photo', $album->photos->count()) }}
                    @if ($album->children->isNotEmpty())
                        &middot; {{ $album->children->count() }} {{ \Illuminate\Support\Str::plural('sub-album', $album->children->count()) }}
                    @endif
                    @if ($album->date_taken)
                        &middot; {{ $album->date_taken->format('M Y') }}
                    @endif
                    @if ($album->location)
                        &middot; {{ $album->location }}
                    @endif
                </div>
                @if ($album->description)
                    <p class="meta mt-2 mb-0">{{ $album->description }}</p>
                @endif
            </div>
        </header>

        @include('partials.site-tabs')
    @endif

    <main class="container py-4">
        @if ($album->children->isNotEmpty())
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-4">
                @foreach ($album->children as $child)
                    <div class="col">
                        <a href="{{ route('albums.show', $child) }}" class="album-card d-block">
                            @if ($child->cover?->thumbnail_path)
                                <img
                                    src="{{ route('photos.thumbnail', $child->cover) }}"
                                    alt="{{ $child->name }}"
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
                                <div class="album-title">{{ $child->name }}</div>
                                <div class="album-meta">
                                    {{ $child->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $child->photos_count) }}
                                    @if ($child->date_taken)
                                        &middot; {{ $child->date_taken->format('M Y') }}
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($album->photos->isEmpty())
            @if ($album->children->isEmpty())
                <div class="empty-state">
                    <p class="mb-0">No photos in this album yet.</p>
                </div>
            @endif
        @else
            <form method="POST" action="{{ route('albums.downloadSelected', $album) }}" id="selectForm">
                @csrf

                <div class="toolbar d-flex justify-content-end gap-2">
                    <button type="button" id="toggleSelect" class="btn btn-tinted btn-sm">Select Photos</button>
                    <button type="submit" id="downloadSelectedBtn" class="btn btn-primary btn-sm d-none" disabled>
                        Download Selected (<span id="selCount">0</span>)
                    </button>
                </div>

                <div class="photo-grid" id="photoGrid"></div>
                <div id="scrollSentinel"></div>
            </form>

            <script type="application/json" id="albumPhotosData">
                @php
                    echo $album->photos->values()->map(fn ($photo, $i) => [
                        'id' => $photo->id,
                        'thumb' => route('photos.thumbnail', $photo),
                        'view' => route('photos.view', [$album, $photo]),
                        'download' => route('photos.download', [$album, $photo]),
                        'filename' => $photo->original_filename,
                        'alt' => $album->name . ' — photo ' . ($i + 1),
                        'aspect' => ($photo->width && $photo->height) ? $photo->width / $photo->height : (4 / 3),
                    ])->toJson();
                @endphp
            </script>
        @endif
    </main>

    @include('partials.site-footer')

    <div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0">
                <div class="modal-header border-0">
                    <span id="lightboxCounter" class="text-body-secondary small"></span>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <a id="lightboxFullsize" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">Full Size</a>
                        <a id="lightboxDownload" href="#" class="btn btn-sm btn-tinted">Download</a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body text-center p-0 position-relative">
                    <div class="lightbox-tap-zone lightbox-tap-prev" aria-hidden="true"></div>
                    <button type="button" id="lightboxPrev" class="lightbox-nav lightbox-prev" aria-label="Previous photo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                        </svg>
                    </button>
                    <img id="lightboxImg" src="" class="img-fluid" alt="">
                    <button type="button" id="lightboxNext" class="lightbox-nav lightbox-next" aria-label="Next photo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                    <div class="lightbox-tap-zone lightbox-tap-next" aria-hidden="true"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const grid = document.getElementById('photoGrid');
        const toggleBtn = document.getElementById('toggleSelect');
        const downloadBtn = document.getElementById('downloadSelectedBtn');
        const selCountEl = document.getElementById('selCount');
        let selectMode = false;

        // Top-rail frame counter (contact-sheet redesign, version-2 only):
        // tracks which photo tile is currently in view as the grid scrolls.
        // Tiles are created incrementally (see renderNextBatch below), so
        // each new tile is added to this same observer as it's created.
        const frameCounterEl = document.getElementById('frameCounter');
        const frameCountObserver = (frameCounterEl && 'IntersectionObserver' in window)
            ? new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                        frameCounterEl.textContent = entry.target.dataset.frame;
                    }
                });
            }, { threshold: [0.5] })
            : null;

        // Full photo list (metadata only — ids/URLs/aspect ratios, a few KB
        // even for a large album) is embedded once in the page. The grid
        // itself is built incrementally in batches as the user scrolls, so
        // the browser never even attempts to fetch thumbnails for photos
        // far below the fold. The lightbox always navigates this full list
        // (not just what's currently rendered), so next/prev never dead-ends
        // even if the grid hasn't scrolled that far yet.
        const dataEl = document.getElementById('albumPhotosData');
        const ALBUM_PHOTOS = dataEl ? JSON.parse(dataEl.textContent) : [];
        const BATCH_SIZE = 30;
        let renderedCount = 0;

        function createTileEl(photo, index) {
            const tile = document.createElement('div');
            tile.className = 'photo-tile';
            tile.dataset.photoId = photo.id;
            tile.dataset.aspect = photo.aspect;
            tile.dataset.frame = String(index + 1).padStart(2, '0');

            const frameNo = document.createElement('span');
            frameNo.className = 'photo-tile-no';
            frameNo.textContent = tile.dataset.frame;
            tile.appendChild(frameNo);

            const img = document.createElement('img');
            img.className = 'photo-tile-img';
            img.loading = 'lazy';
            img.alt = photo.alt;
            img.src = photo.thumb;
            tile.appendChild(img);

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'photo-tile-checkbox';
            checkbox.name = 'photo_ids[]';
            checkbox.value = photo.id;
            tile.appendChild(checkbox);

            const downloadLink = document.createElement('a');
            downloadLink.href = photo.download;
            downloadLink.className = 'photo-tile-download';
            downloadLink.title = 'Download';
            downloadLink.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>';
            tile.appendChild(downloadLink);

            return tile;
        }

        function renderNextBatch() {
            if (!grid || renderedCount >= ALBUM_PHOTOS.length) {
                return;
            }

            const nextPhotos = ALBUM_PHOTOS.slice(renderedCount, renderedCount + BATCH_SIZE);
            const startIndex = renderedCount;
            nextPhotos.forEach((photo, i) => {
                const tile = createTileEl(photo, startIndex + i);
                grid.appendChild(tile);
                if (frameCountObserver) {
                    frameCountObserver.observe(tile);
                }
            });
            renderedCount += nextPhotos.length;

            layoutJustified();

            if (renderedCount >= ALBUM_PHOTOS.length && sentinelObserver) {
                sentinelObserver.disconnect();
            }
        }

        // Justified-rows layout. The JS ONLY decides which photos share a
        // row (grouping greedily to a target height). The actual fill is done
        // by flexbox: each tile in a full row gets flex-grow proportional to
        // its aspect ratio with a zero basis, so the row always stretches to
        // exactly 100% of its container — no matter what width was measured.
        // This makes the fill immune to width mis-measurement/timing races;
        // a wrong measurement only changes how many photos land in a row, not
        // whether the row fills. Nothing is ever cropped (each tile keeps its
        // true aspect ratio via aspect-ratio).
        function tileAspect(tile) {
            return parseFloat(tile.dataset.aspect) || (4 / 3);
        }

        function layoutJustified() {
            if (!grid) {
                return;
            }

            const currentTiles = Array.from(grid.querySelectorAll('.photo-tile'));
            if (currentTiles.length === 0) {
                return;
            }

            const containerWidth = grid.clientWidth;
            if (containerWidth <= 0) {
                return;
            }

            const targetHeight = containerWidth < 576 ? 150 : (containerWidth < 992 ? 200 : 260);

            // Group tiles into rows: keep adding until the row, laid out at the
            // target height, would reach the container width.
            const rows = [];
            let current = [];
            let widthAtTarget = 0;

            currentTiles.forEach((tile) => {
                current.push(tile);
                widthAtTarget += targetHeight * tileAspect(tile);
                if (widthAtTarget >= containerWidth) {
                    rows.push({ tiles: current, full: true });
                    current = [];
                    widthAtTarget = 0;
                }
            });
            if (current.length > 0) {
                rows.push({ tiles: current, full: false });
            }

            const fragment = document.createDocumentFragment();

            rows.forEach(({ tiles, full }) => {
                const rowEl = document.createElement('div');
                rowEl.className = 'photo-row';

                tiles.forEach((tile) => {
                    const aspect = tileAspect(tile);
                    tile.style.aspectRatio = aspect;

                    if (full) {
                        // flexbox stretches the row to fill 100% width; because
                        // grow is proportional to aspect with a 0 basis, every
                        // tile ends up the same height and the row fills exactly.
                        tile.style.flexGrow = aspect;
                        tile.style.flexShrink = '1';
                        tile.style.flexBasis = '0';
                        tile.style.width = '';
                    } else {
                        // Last, partial row: fixed natural size, left-aligned —
                        // don't blow a lone leftover photo up to full width.
                        tile.style.flexGrow = '0';
                        tile.style.flexShrink = '0';
                        tile.style.flexBasis = 'auto';
                        tile.style.width = `${Math.round(targetHeight * aspect)}px`;
                    }

                    rowEl.appendChild(tile);
                });

                fragment.appendChild(rowEl);
            });

            grid.replaceChildren(fragment);
        }

        function updateCount() {
            const count = grid.querySelectorAll('.photo-tile-checkbox:checked').length;
            selCountEl.textContent = count;
            downloadBtn.disabled = count === 0;
        }

        let lightboxIndex = -1;
        const lightboxPrevBtn = document.getElementById('lightboxPrev');
        const lightboxNextBtn = document.getElementById('lightboxNext');
        const lightboxImgEl = document.getElementById('lightboxImg');

        // Full-size photos can be several MB, so navigating the lightbox
        // would otherwise mean waiting on a fresh multi-MB download every
        // time. Two things fix that: (1) show the thumbnail instantly, then
        // swap to the full-res image once it's loaded, so something sharp
        // appears immediately instead of a blank/spinner gap; (2) preload
        // the next/previous photo's full-res image in the background as
        // soon as a photo is shown, so by the time you click next/prev it's
        // usually already sitting in the browser cache and the swap is
        // instant.
        const preloadedUrls = new Set();
        let loadToken = 0;

        function preloadPhoto(index) {
            if (ALBUM_PHOTOS.length === 0) {
                return;
            }

            const url = ALBUM_PHOTOS[(index + ALBUM_PHOTOS.length) % ALBUM_PHOTOS.length].view;
            if (preloadedUrls.has(url)) {
                return;
            }

            preloadedUrls.add(url);
            new Image().src = url;
        }

        function showLightboxPhoto(index) {
            if (ALBUM_PHOTOS.length === 0) {
                return;
            }

            lightboxIndex = (index + ALBUM_PHOTOS.length) % ALBUM_PHOTOS.length;
            const photo = ALBUM_PHOTOS[lightboxIndex];
            const myToken = ++loadToken;

            lightboxImgEl.src = photo.thumb;
            lightboxImgEl.alt = photo.alt;

            const fullImg = new Image();
            fullImg.onload = () => {
                if (myToken === loadToken) {
                    lightboxImgEl.src = fullImg.src;
                }
            };
            fullImg.src = photo.view;
            preloadedUrls.add(photo.view);

            document.getElementById('lightboxDownload').href = photo.download;
            document.getElementById('lightboxFullsize').href = photo.view;
            document.getElementById('lightboxCounter').textContent = `${lightboxIndex + 1} / ${ALBUM_PHOTOS.length}`;

            const hasMultiple = ALBUM_PHOTOS.length > 1;
            if (lightboxPrevBtn) {
                lightboxPrevBtn.classList.toggle('d-none', !hasMultiple);
            }
            if (lightboxNextBtn) {
                lightboxNextBtn.classList.toggle('d-none', !hasMultiple);
            }

            preloadPhoto(lightboxIndex + 1);
            preloadPhoto(lightboxIndex - 1);
        }

        function openLightbox(photoId) {
            showLightboxPhoto(ALBUM_PHOTOS.findIndex((p) => p.id === photoId));
            new bootstrap.Modal(document.getElementById('lightboxModal')).show();
        }

        if (lightboxPrevBtn) {
            lightboxPrevBtn.addEventListener('click', () => showLightboxPhoto(lightboxIndex - 1));
        }

        if (lightboxNextBtn) {
            lightboxNextBtn.addEventListener('click', () => showLightboxPhoto(lightboxIndex + 1));
        }

        const lightboxTapPrev = document.querySelector('.lightbox-tap-prev');
        const lightboxTapNext = document.querySelector('.lightbox-tap-next');

        if (lightboxTapPrev) {
            lightboxTapPrev.addEventListener('click', () => showLightboxPhoto(lightboxIndex - 1));
        }

        if (lightboxTapNext) {
            lightboxTapNext.addEventListener('click', () => showLightboxPhoto(lightboxIndex + 1));
        }

        const lightboxModalEl = document.getElementById('lightboxModal');
        if (lightboxModalEl) {
            lightboxModalEl.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    showLightboxPhoto(lightboxIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    showLightboxPhoto(lightboxIndex + 1);
                }
            });

            // Swipe left/right on mobile to move between photos.
            let touchStartX = 0;
            let touchStartY = 0;
            let touchStartTime = 0;

            lightboxModalEl.addEventListener('touchstart', (e) => {
                if (e.touches.length !== 1) {
                    return;
                }
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                touchStartTime = Date.now();
            }, { passive: true });

            lightboxModalEl.addEventListener('touchend', (e) => {
                if (e.changedTouches.length !== 1) {
                    return;
                }
                const dx = e.changedTouches[0].clientX - touchStartX;
                const dy = e.changedTouches[0].clientY - touchStartY;
                const elapsed = Date.now() - touchStartTime;

                const isHorizontalSwipe = Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5 && elapsed < 600;
                if (isHorizontalSwipe) {
                    showLightboxPhoto(dx < 0 ? lightboxIndex + 1 : lightboxIndex - 1);
                }
            }, { passive: true });
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                selectMode = !selectMode;
                grid.classList.toggle('select-mode', selectMode);
                toggleBtn.textContent = selectMode ? 'Cancel' : 'Select Photos';
                downloadBtn.classList.toggle('d-none', !selectMode);
                if (!selectMode) {
                    grid.querySelectorAll('.photo-tile-checkbox').forEach(cb => cb.checked = false);
                }
                updateCount();
            });

            grid.addEventListener('click', (e) => {
                if (e.target.closest('.photo-tile-download')) {
                    return;
                }

                const tile = e.target.closest('.photo-tile');
                if (!tile) {
                    return;
                }

                if (selectMode) {
                    const checkbox = tile.querySelector('.photo-tile-checkbox');
                    checkbox.checked = !checkbox.checked;
                    updateCount();
                } else {
                    openLightbox(Number(tile.dataset.photoId));
                }
            });
        }

        // Re-run once everything (CSS, fonts, images) has fully settled —
        // corrects any mismeasurement from the immediate call above, which
        // can read a narrower width than the container's final layout.
        window.addEventListener('load', layoutJustified);

        if (grid) {
            let resizeTimer;
            new ResizeObserver(() => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(layoutJustified, 120);
            }).observe(grid);
        }

        // Load more photos as the user scrolls near the bottom. rootMargin
        // starts the next batch a bit before the sentinel actually enters
        // the viewport, so new rows are ready before you reach them.
        let sentinelObserver = null;
        const sentinel = document.getElementById('scrollSentinel');
        if (sentinel && ALBUM_PHOTOS.length > 0) {
            sentinelObserver = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    renderNextBatch();
                }
            }, { rootMargin: '800px 0px' });
            sentinelObserver.observe(sentinel);
        }

        renderNextBatch();
    </script>
</body>
</html>
