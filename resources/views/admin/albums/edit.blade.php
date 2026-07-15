<x-app-layout>
    <x-slot name="header">
        <h1>Edit Album</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="row justify-content-center g-3">
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

                <form method="POST" action="{{ route('admin.albums.update', $album) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Title</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $album->name) }}"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  class="form-control">{{ old('description', $album->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="date_taken" class="form-label">Date of capture</label>
                        <input type="date" name="date_taken" id="date_taken"
                               value="{{ old('date_taken', optional($album->date_taken)->format('Y-m-d')) }}"
                               class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Place</label>
                        <input type="text" name="location" id="location" value="{{ old('location', $album->location) }}"
                               placeholder="e.g. Riga, Latvia"
                               class="form-control">
                    </div>

                    @if ($album->parent_id === null)
                        <div class="mb-4">
                            <label for="parent_id" class="form-label">Parent Album</label>
                            <select name="parent_id" id="parent_id" class="form-select">
                                <option value="">— None (top-level album) —</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}" @selected(old('parent_id', $album->parent_id) == $option->id)>
                                        {{ $option->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text card-muted">Only top-level albums can be chosen as a parent (albums are two levels deep).</div>
                        </div>
                    @else
                        <input type="hidden" name="parent_id" value="{{ $album->parent_id }}">
                        <p class="mb-4 form-text card-muted">
                            This is a sub-album of <span class="fw-medium">{{ $album->parent?->name }}</span>.
                        </p>
                    @endif

                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary">
                            Save Changes
                        </button>
                        <a href="{{ route('admin.albums.index') }}" class="link-secondary">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>

        <div class="col-lg-8 col-xl-6">
            <div class="card p-4">
                <h2 class="h6 card-muted text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 0.03em;">Thumbnail</h2>
                <p class="form-text card-muted mb-3">Click a photo to make it the album's cover. Hover to preview it larger.</p>

                @if ($photos->isEmpty())
                    <p class="card-muted mb-0">No photos uploaded to this album yet.</p>
                @else
                    <div class="photo-pick-grid">
                        @foreach ($photos as $photo)
                            <div class="photo-pick-wrap {{ $album->cover_photo_id === $photo->id ? 'is-cover' : '' }}">
                                <form method="POST" action="{{ route('admin.albums.setCover', $album) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="photo_id" value="{{ $photo->id }}">
                                    <button type="submit" class="photo-pick-btn" title="{{ $photo->original_filename }}">
                                        @if ($photo->thumbnail_path)
                                            <img src="{{ route('photos.thumbnail', $photo) }}" class="photo-pick" alt="{{ $photo->original_filename }}">
                                        @else
                                            <span class="photo-pick photo-pick-pending" title="Thumbnail still processing"></span>
                                        @endif
                                    </button>
                                </form>
                                @if ($album->cover_photo_id === $photo->id)
                                    <span class="photo-pick-badge">Cover</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
