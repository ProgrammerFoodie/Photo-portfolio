<div class="site-tabs">
    <div class="container">
        <ul class="site-tabs-list">
            <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home', 'albums.show') ? 'active' : '' }}">Albums</a></li>
            <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a></li>
            <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a></li>
        </ul>
    </div>
</div>
