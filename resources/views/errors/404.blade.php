<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
@php
    $metaDescription = 'Page not found — ' . \App\Models\Setting::get('site_title');
@endphp
@include('partials.site-head', ['pageTitle' => 'Page not found · ' . \App\Models\Setting::get('site_title'), 'metaDescription' => $metaDescription])
</head>
<body>

    <main class="container py-4" style="min-height: 60vh;">
        @if (\App\Support\Theme::is('version-2'))
            @include('partials.error-mosaic', ['code' => '404'])
        @else
            <h1 class="page-hero-title text-center mb-4">404</h1>
        @endif

        <p class="text-body-secondary mb-4 text-center">This page doesn't exist, or has been moved.</p>
        <div class="text-center">
            <a href="{{ route('home') }}" class="btn btn-primary">Back to albums</a>
        </div>
    </main>

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
