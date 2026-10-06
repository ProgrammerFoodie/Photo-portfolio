<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1>Albums</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.icloud.create') }}" class="btn btn-tinted btn-sm">Link iCloud Album</a>
                <a href="{{ route('admin.albums.create') }}" class="btn btn-primary btn-sm">+ New Album</a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr class="card-muted">
                        <th class="ps-4">Title</th>
                        <th>Parent</th>
                        <th>Photos</th>
                        <th>Size</th>
                        <th>Downloads</th>
                        <th>Date Taken</th>
                        <th>iCloud</th>
                        <th class="pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($albums as $album)
                        <tr>
                            <td class="ps-4 fw-medium">{{ $album->name }}</td>
                            <td class="card-muted">{{ $album->parent?->name ?? '—' }}</td>
                            <td>{{ $album->photos_count }}</td>
                            <td>{{ $album->size_human }}</td>
                            <td>{{ $album->downloads_count }}</td>
                            <td class="card-muted">{{ optional($album->date_taken)->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @if ($album->icloud_token)
                                    @if ($album->icloud_sync_status === 'failed')
                                        <span class="badge text-bg-danger" title="{{ $album->icloud_sync_error }}">Failed</span>
                                    @elseif ($album->icloud_sync_status === 'syncing')
                                        <span class="badge text-bg-warning">Syncing…</span>
                                    @else
                                        <span class="badge text-bg-success">Linked</span>
                                    @endif

                                    @unless ($album->icloud_auto_sync)
                                        <span class="badge text-bg-secondary">Paused</span>
                                    @endunless

                                    <div class="card-muted small mt-1">
                                        {{ $album->icloud_last_synced_at?->diffForHumans() ?? 'never synced' }}
                                    </div>
                                @else
                                    <span class="card-muted">—</span>
                                @endif
                            </td>
                            <td class="pe-4">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="{{ route('admin.albums.edit', $album) }}" class="link-primary">
                                        Edit
                                    </a>
                                    @if ($album->icloud_token)
                                        <form method="POST" action="{{ route('admin.icloud.sync', $album) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link link-primary p-0 border-0 align-baseline">Sync now</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.albums.destroy', $album) }}"
                                          onsubmit="return confirm('Delete &quot;{{ $album->name }}&quot; and all its photos? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link link-danger p-0 border-0 align-baseline">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center card-muted py-5">No albums yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $albums->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
