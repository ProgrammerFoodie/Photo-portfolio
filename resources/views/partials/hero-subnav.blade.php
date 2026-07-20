@php $showLogin = $showLogin ?? false; @endphp
<nav class="hero-subnav">
    <div class="container d-flex justify-content-between align-items-center">
        <ul class="hero-subnav-list">
            <li>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home', 'albums.show') ? 'active' : '' }}">
                    <i class="bi bi-images"></i> Albums
                </a>
            </li>
            <li>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">
                    <i class="bi bi-person"></i> About
                </a>
            </li>
            <li>
                <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">
                    <i class="bi bi-envelope"></i> Contact
                </a>
            </li>
        </ul>

        @if ($showLogin)
            <a href="{{ route('login') }}" class="hero-subnav-login" aria-label="Login" title="Login">
                <i class="bi bi-box-arrow-in-right"></i>
            </a>
        @endif
    </div>
</nav>
