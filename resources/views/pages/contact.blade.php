<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
@php
    $metaDescription = filled($body) ? \Illuminate\Support\Str::limit(strip_tags($body), 160) : 'Get in touch with ' . \App\Models\Setting::get('site_title');
    $metaImage = \App\Models\Setting::get('profile_cover_path') ? route('profile.cover') : null;
@endphp
@include('partials.site-head', ['pageTitle' => $title . ' · ' . \App\Models\Setting::get('site_title'), 'metaDescription' => $metaDescription, 'metaImage' => $metaImage])
@if (\App\Support\Theme::is('version-2'))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Contact-sheet redesign (version-2 only) -- see about.blade.php for
           the rails/identity notes; same pattern reused here. Form styled
           as a "caption sheet" (bordered fields, mono uppercase labels)
           per docs/contact-sheet-implementation-plan.md Section 6. */
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

        .cs-page-body { padding: 0 clamp(20px, 5vw, 56px) clamp(56px, 9vh, 96px); display: grid; grid-template-columns: 1.1fr 1fr; gap: clamp(32px, 6vw, 72px); }
        .cs-page-body p { font-size: 14px; line-height: 1.8; color: var(--cs-ink-soft, rgba(21,24,26,.62)); }

        .cs-form-group { margin-bottom: 22px; }
        .cs-form-group label { display: block; font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--cs-ink-soft, rgba(21,24,26,.62)); margin-bottom: 8px; }
        .cs-form-group .form-control {
            border: 1px solid var(--cs-rule, rgba(21,24,26,.16));
            border-radius: 0;
            background: transparent;
            color: var(--cs-ink, #15181a);
            font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace;
            font-size: 13px;
            padding: 10px 12px;
        }
        .cs-form-group .form-control:focus {
            border-color: var(--cs-grease, #a91f28);
            box-shadow: none;
            background: transparent;
            color: var(--cs-ink, #15181a);
        }
        .cs-submit-btn {
            border: 1px solid var(--cs-ink, #15181a);
            background: var(--cs-ink, #15181a);
            color: var(--cs-lightbox, #f2f4f1);
            border-radius: 0;
            font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace;
            font-size: 12px;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 12px 28px;
        }
        .cs-submit-btn:hover { background: var(--cs-grease, #a91f28); border-color: var(--cs-grease, #a91f28); color: var(--cs-lightbox, #f2f4f1); }
        .cs-alert { border: 1px solid var(--cs-rule, rgba(21,24,26,.16)); padding: 12px 14px; font-size: 12px; margin-bottom: 20px; }
        .cs-alert-success { border-color: #2f6f4e; color: #2f6f4e; }
        .cs-alert-danger { border-color: var(--cs-grease, #a91f28); color: var(--cs-grease, #a91f28); }

        /* Brand colors on each icon/label come from their own inline style
           (unchanged) -- only typography is tweaked here to match the page. */
        .cs-social-links a { font-size: 12px; letter-spacing: .06em; text-transform: uppercase; font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace; }

        body.cs-home footer {
            padding-bottom: 34px;
            color: var(--cs-ink-faint, rgba(21, 24, 26, .38));
            border-top-color: var(--cs-rule, rgba(21, 24, 26, .16));
        }

        @media (max-width: 767.98px) {
            .cs-identity, .cs-page-head, .cs-page-body { padding-left: 20px; padding-right: 20px; }
            .cs-page-body { grid-template-columns: 1fr; }
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
            <div>
                @if (session('status'))
                    <div class="cs-alert cs-alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="cs-alert cs-alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.submit') }}">
                    @csrf

                    <div style="position: absolute; left: -9999px;" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="cs-form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="form-control" autocomplete="name" required>
                    </div>

                    <div class="cs-form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="form-control" autocomplete="email" required>
                    </div>

                    <div class="cs-form-group">
                        <label for="message">Message</label>
                        <textarea name="message" id="message" rows="5"
                                  class="form-control" required>{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="cs-submit-btn">Send Message</button>
                </form>
            </div>

            <div>
                <p>{!! nl2br(e($body)) !!}</p>

                @if (!empty($socialLinks))
                    @php
                        $socialMeta = [
                            'instagram' => ['bi-instagram', '#E1306C'],
                            'facebook' => ['bi-facebook', '#1877F2'],
                            'twitter' => ['bi-twitter-x', '#FFFFFF'],
                            'x' => ['bi-twitter-x', '#FFFFFF'],
                            'youtube' => ['bi-youtube', '#FF0000'],
                            'tiktok' => ['bi-tiktok', '#FFFFFF'],
                            'linkedin' => ['bi-linkedin', '#0A66C2'],
                            'whatsapp' => ['bi-whatsapp', '#25D366'],
                            'pinterest' => ['bi-pinterest', '#E60023'],
                            'threads' => ['bi-threads', '#FFFFFF'],
                            'telegram' => ['bi-telegram', '#26A5E4'],
                            'snapchat' => ['bi-snapchat', '#FFFC00'],
                            'vimeo' => ['bi-vimeo', '#1AB7EA'],
                            'github' => ['bi-github', '#FFFFFF'],
                            'flickr' => ['bi-flickr', '#FF0084'],
                            'twitch' => ['bi-twitch', '#9146FF'],
                            'discord' => ['bi-discord', '#5865F2'],
                            'email' => ['bi-envelope-fill', '#EAEAEA'],
                            'mail' => ['bi-envelope-fill', '#EAEAEA'],
                        ];
                    @endphp

                    @php
                        $instagramGradient = 'linear-gradient(45deg, #FEDA75 5%, #FA7E1E 25%, #D62976 45%, #962FBF 70%, #4F5BD5 95%)';
                    @endphp
                    <div class="d-flex flex-wrap gap-3 cs-social-links">
                        @foreach ($socialLinks as $link)
                            @php
                                $matchedKey = collect($socialMeta)->keys()->first(
                                    fn ($key) => str_contains(strtolower($link['label']), $key)
                                );
                                [$icon, $color] = $socialMeta[$matchedKey] ?? ['bi-link-45deg', '#EAEAEA'];
                                $isInstagram = $matchedKey === 'instagram';
                            @endphp
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                               class="d-inline-flex align-items-center gap-2 text-decoration-none"
                               style="color: {{ $color }}; font-size: 1.15rem;">
                                <i class="bi {{ $icon }}" aria-hidden="true"
                                   style="font-size: 1.75rem; @if ($isInstagram) background: {{ $instagramGradient }}; -webkit-background-clip: text; background-clip: text; color: transparent; @endif"></i>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
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
            <div class="row justify-content-center">
            <div class="col-xl-10">
            <div class="row">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <div class="card p-4" style="max-width: 32rem;">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('contact.submit') }}">
                            @csrf

                            <div style="position: absolute; left: -9999px;" aria-hidden="true">
                                <label for="website">Website</label>
                                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                       class="form-control" autocomplete="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="form-control" autocomplete="email" required>
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label">Message</label>
                                <textarea name="message" id="message" rows="5"
                                          class="form-control" required>{{ old('message') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <p class="text-body-secondary">{!! nl2br(e($body)) !!}</p>

                    @if (!empty($socialLinks))
                        @php
                            $socialMeta = [
                                'instagram' => ['bi-instagram', '#E1306C'],
                                'facebook' => ['bi-facebook', '#1877F2'],
                                'twitter' => ['bi-twitter-x', '#FFFFFF'],
                                'x' => ['bi-twitter-x', '#FFFFFF'],
                                'youtube' => ['bi-youtube', '#FF0000'],
                                'tiktok' => ['bi-tiktok', '#FFFFFF'],
                                'linkedin' => ['bi-linkedin', '#0A66C2'],
                                'whatsapp' => ['bi-whatsapp', '#25D366'],
                                'pinterest' => ['bi-pinterest', '#E60023'],
                                'threads' => ['bi-threads', '#FFFFFF'],
                                'telegram' => ['bi-telegram', '#26A5E4'],
                                'snapchat' => ['bi-snapchat', '#FFFC00'],
                                'vimeo' => ['bi-vimeo', '#1AB7EA'],
                                'github' => ['bi-github', '#FFFFFF'],
                                'flickr' => ['bi-flickr', '#FF0084'],
                                'twitch' => ['bi-twitch', '#9146FF'],
                                'discord' => ['bi-discord', '#5865F2'],
                                'email' => ['bi-envelope-fill', '#EAEAEA'],
                                'mail' => ['bi-envelope-fill', '#EAEAEA'],
                            ];
                        @endphp

                        @php
                            $instagramGradient = 'linear-gradient(45deg, #FEDA75 5%, #FA7E1E 25%, #D62976 45%, #962FBF 70%, #4F5BD5 95%)';
                        @endphp
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($socialLinks as $link)
                                @php
                                    $matchedKey = collect($socialMeta)->keys()->first(
                                        fn ($key) => str_contains(strtolower($link['label']), $key)
                                    );
                                    [$icon, $color] = $socialMeta[$matchedKey] ?? ['bi-link-45deg', '#EAEAEA'];
                                    $isInstagram = $matchedKey === 'instagram';
                                @endphp
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="d-inline-flex align-items-center gap-2 text-decoration-none"
                                   style="color: {{ $color }}; font-size: 1.15rem;">
                                    <i class="bi {{ $icon }}" aria-hidden="true"
                                       style="font-size: 1.75rem; @if ($isInstagram) background: {{ $instagramGradient }}; -webkit-background-clip: text; background-clip: text; color: transparent; @endif"></i>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            </div>
            </div>
        </main>
    @endif

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
