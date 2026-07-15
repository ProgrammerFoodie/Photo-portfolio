<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1>Albums</h1>
            <a href="{{ route('admin.albums.create') }}" class="btn btn-primary btn-sm">+ New Album</a>
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
                            <td class="pe-4">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="{{ route('admin.albums.edit', $album) }}" class="link-primary">
                                        Edit
                                    </a>
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
                            <td colspan="7" class="text-center card-muted py-5">No albums yet.</td>
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
