@php $overlay = $overlay ?? false; @endphp
<nav class="navbar sticky-top py-2 @if ($overlay) nav-overlay @endif" id="siteNav">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand mb-0" href="{{ route('home') }}">{{ \App\Models\Setting::get('site_title') }}</a>

        <div class="d-flex align-items-center gap-3">
            @if (\App\Support\Theme::is('version-2'))
                <a class="nav-link p-0" style="color: var(--text-muted); font-size: 0.9rem;" href="{{ route('about') }}">About</a>
                <a class="nav-link p-0" style="color: var(--text-muted); font-size: 0.9rem;" href="{{ route('contact') }}">Contact</a>
            @endif
            <a class="nav-link-cta" href="{{ route('login') }}">Login</a>
        </div>
    </div>
</nav>

@if ($overlay)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var nav = document.getElementById('siteNav');
            var hero = document.querySelector('.hero-grid');
            if (!nav || !hero) return;

            function onScroll() {
                if (window.scrollY > hero.offsetHeight - 80) {
                    nav.classList.add('nav-visible');
                } else {
                    nav.classList.remove('nav-visible');
                }
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        });
    </script>
@endif
