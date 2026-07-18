<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} &middot; {{ \App\Models\Setting::get('site_title') }}</title>

    @include('partials.site-styles')
</head>
<body>

    @include('partials.site-nav')

    @php
        $coverPath = \App\Models\Setting::get('profile_cover_path');
    @endphp
    <header class="profile-header" @if ($coverPath) style="background-image: url('{{ route('profile.cover') }}');" @endif>
        <div class="container">
            <h1 class="page-hero-title mb-0">{{ $title }}</h1>
        </div>
    </header>

    @include('partials.site-tabs')

    <main class="container py-4" style="min-height: 40vh;">
        <div class="row justify-content-center">
        <div class="col-xl-10">
        <div class="row">
            <div class="col-lg-5 mb-4 mb-lg-0">
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

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                   class="form-control" required>
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

            <div class="col-lg-7">
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

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
