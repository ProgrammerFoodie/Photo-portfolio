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
        <p class="text-body-secondary" style="max-width: 45rem;">{!! nl2br(e($body)) !!}</p>
    </main>

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
