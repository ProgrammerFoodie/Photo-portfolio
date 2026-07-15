<nav class="navbar sticky-top py-2">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand mb-0" href="{{ route('home') }}">{{ \App\Models\Setting::get('site_title') }}</a>
        <a class="nav-link-cta" href="{{ route('login') }}">Login</a>
    </div>
</nav>
