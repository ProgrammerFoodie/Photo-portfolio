<x-app-layout>
    <x-slot name="header">
        <h1>Dashboard</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
        <div class="col">
            <div class="card stat-card d-flex flex-row align-items-start justify-content-between">
                <div>
                    <div class="stat-label">Total Photos</div>
                    <div class="stat-value">{{ number_format($stats['total_photos']) }}</div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                        <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card d-flex flex-row align-items-start justify-content-between">
                <div>
                    <div class="stat-label">Size on Disk</div>
                    <div class="stat-value">{{ $stats['disk_used_human'] }} <span class="card-muted" style="font-size: 1rem; font-weight: 400;">/ {{ $stats['disk_total_human'] }}</span></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v8h12V4H2z"/>
                        <path d="M4 6h8v1H4V6zm0 2h5v1H4V8z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card d-flex flex-row align-items-start justify-content-between">
                <div>
                    <div class="stat-label">Albums</div>
                    <div class="stat-value">{{ number_format($stats['total_albums']) }}</div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM8.5 2h5A1.5 1.5 0 0 1 15 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 7 12.5v-9A1.5 1.5 0 0 1 8.5 2zM1.5 9h3A1.5 1.5 0 0 1 6 10.5v3A1.5 1.5 0 0 1 4.5 15h-3A1.5 1.5 0 0 1 0 13.5v-3A1.5 1.5 0 0 1 1.5 9z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card d-flex flex-row align-items-start justify-content-between">
                <div>
                    <div class="stat-label">Downloads</div>
                    <div class="stat-value">{{ number_format($stats['total_downloads']) }}</div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="col">
            <a href="{{ route('admin.messages.index') }}" class="text-decoration-none">
                <div class="card stat-card d-flex flex-row align-items-start justify-content-between">
                    <div>
                        <div class="stat-label">New Messages</div>
                        <div class="stat-value">{{ number_format($unreadMessagesCount) }}</div>
                    </div>
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                        </svg>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
        <div class="col">
            <div class="card stat-card">
                <div class="stat-label">Uploads This Week</div>
                <div class="stat-value">{{ number_format($stats['uploads_this_week']) }}</div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="stat-label">Most Downloaded Album</div>
                @if ($stats['top_album_slug'])
                    <a href="{{ route('albums.show', $stats['top_album_slug']) }}" target="_blank" rel="noopener" class="stat-value d-block text-truncate" title="{{ $stats['top_album_name'] }}">
                        {{ $stats['top_album_name'] }}
                    </a>
                    <div class="card-muted small">{{ number_format($stats['top_album_downloads']) }} downloads</div>
                @else
                    <div class="card-muted">No downloads yet</div>
                @endif
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="stat-label">Most Downloaded Photo</div>
                @if ($stats['top_photo_id'])
                    <a href="{{ route('photos.view', ['album' => $stats['top_photo_album_slug'], 'photo' => $stats['top_photo_id']]) }}"
                       target="_blank" rel="noopener" class="d-flex align-items-center gap-2 text-decoration-none">
                        <img src="{{ route('photos.thumbnail', $stats['top_photo_id']) }}" alt=""
                             width="48" height="48" style="object-fit: cover; border-radius: 0.5rem; flex-shrink: 0;">
                        <span class="text-truncate">
                            <span class="stat-value d-block text-truncate" style="font-size: 1rem;" title="{{ $stats['top_photo_name'] }}">
                                {{ $stats['top_photo_name'] }}
                            </span>
                            <span class="card-muted small">{{ number_format($stats['top_photo_downloads']) }} downloads</span>
                        </span>
                    </a>
                @else
                    <div class="card-muted">No downloads yet</div>
                @endif
            </div>
        </div>
    </div>

    @if ($recentMessages->isNotEmpty())
        <div class="card mb-4">
            <div class="p-4 pb-2 d-flex align-items-center justify-content-between">
                <h6 class="card-muted text-uppercase mb-0" style="font-size: 0.8rem; letter-spacing: 0.03em;">Recent Messages</h6>
                <a href="{{ route('admin.messages.index') }}" class="link-primary small">View all</a>
            </div>
            <div class="list-group list-group-flush">
                @foreach ($recentMessages as $message)
                    <a href="{{ route('admin.messages.show', $message) }}"
                       class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center">
                        <div>
                            @if ($message->read_at === null)
                                <span class="badge text-bg-primary me-1">New</span>
                            @endif
                            <span class="fw-medium">{{ $message->name }}</span>
                            <span class="card-muted"> — {{ \Illuminate\Support\Str::limit($message->message, 60) }}</span>
                        </div>
                        <span class="card-muted small">{{ $message->created_at->diffForHumans() }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card p-4 d-flex flex-row flex-wrap gap-2">
        <a href="{{ route('admin.albums.index') }}" class="btn btn-outline-light btn-sm">Manage Albums</a>
        <a href="{{ route('admin.albums.create') }}" class="btn btn-primary btn-sm">+ New Album</a>
        <a href="{{ route('upload.form') }}" class="btn btn-outline-light btn-sm">Upload Photos</a>
    </div>
</x-app-layout>
