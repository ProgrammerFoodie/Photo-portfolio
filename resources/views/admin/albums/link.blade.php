<x-app-layout>
    <x-slot name="header">
        <h1>Link iCloud Album</h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card p-4">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="card-muted">
                    In Photos, share an album and turn on <strong>Public Website</strong>, then paste
                    the link here. A new album is created from it and its photos import in the
                    background. iCloud stays the source of truth — new photos you add there appear
                    here within the hour.
                </p>

                <form method="POST" action="{{ route('admin.icloud.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="link" class="form-label">Shared album link</label>
                        <input type="text" name="link" id="link" value="{{ old('link') }}"
                               placeholder="https://www.icloud.com/sharedalbum/#B0..."
                               class="form-control" required autofocus>
                        <div class="form-text card-muted">
                            The album must be shared publicly. Private shares can't be read.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Title (optional)</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="form-control">
                        <div class="form-text card-muted">Leave blank to use the album's iCloud name.</div>
                    </div>

                    <div class="mb-4">
                        <label for="parent_id" class="form-label">Parent Album (optional)</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">— None (top-level album) —</option>
                            @foreach ($parentOptions as $option)
                                <option value="{{ $option->id }}" @selected(old('parent_id') == $option->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-secondary card-muted small">
                        Shared albums are served at about 2048px on the long edge, so these are
                        display copies rather than your full-resolution originals.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Link &amp; Import</button>
                        <a href="{{ route('admin.albums.index') }}" class="btn btn-tinted">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
