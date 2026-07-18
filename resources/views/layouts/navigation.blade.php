<nav class="navbar navbar-expand-md admin-navbar sticky-top py-3">
    <div class="container-xl">
        <a class="navbar-brand" href="{{ route('dashboard') }}">{{ \App\Models\Setting::get('site_title') }}</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto gap-md-4">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-nav-match="dashboard" href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.albums.*') ? 'active' : '' }}" data-nav-match="albums" href="{{ route('admin.albums.index') }}">
                        Albums
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('upload.form') ? 'active' : '' }}" data-nav-match="upload" href="{{ route('upload.form') }}">
                        Upload
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" data-nav-match="settings" href="{{ route('admin.settings.edit') }}">
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" data-nav-match="messages" href="{{ route('admin.messages.index') }}">
                        Messages
                        @php($unreadNavCount = \App\Models\ContactMessage::whereNull('read_at')->count())
                        @if ($unreadNavCount > 0)
                            <span class="badge text-bg-primary">{{ $unreadNavCount }}</span>
                        @endif
                    </a>
                </li>
                @can('manage-users')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" data-nav-match="users" href="{{ route('admin.users.index') }}">
                            Users
                        </a>
                    </li>
                @endcan
            </ul>

            <ul class="navbar-nav align-items-md-center">
                <li class="nav-item" id="nav-upload-status" style="display: none;">
                    <span class="badge text-bg-primary">Uploading&hellip;</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}" target="_blank" rel="noopener">View Site</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Log Out</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
