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

        @if ($album->icloud_token)
            <div class="col-lg-8 col-xl-6">
                <div class="card p-4">
                    <h2 class="h6 card-muted text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.03em;">iCloud</h2>

                    @if ($album->icloud_sync_status === 'failed')
                        <div class="alert alert-danger">
                            <strong>Last sync failed.</strong>
                            <div class="small mt-1">{{ $album->icloud_sync_error }}</div>
                            <div class="small mt-2 mb-0">
                                Photos already imported are untouched. If you stopped sharing this
                                album in Photos, re-share it or unlink below.
                            </div>
                        </div>
                    @endif

                    <dl class="row mb-3 small">
                        <dt class="col-4 card-muted fw-normal">Status</dt>
                        <dd class="col-8">{{ ucfirst($album->icloud_sync_status ?? 'idle') }}</dd>

                        <dt class="col-4 card-muted fw-normal">Last synced</dt>
                        <dd class="col-8">{{ $album->icloud_last_synced_at?->diffForHumans() ?? 'never' }}</dd>

                        <dt class="col-4 card-muted fw-normal">Photos</dt>
                        <dd class="col-8">{{ $album->photos()->whereNotNull('icloud_photo_guid')->count() }} from iCloud</dd>

                        <dt class="col-4 card-muted fw-normal">Token</dt>
                        <dd class="col-8"><code>{{ $album->icloud_token }}</code></dd>
                    </dl>

                    <p class="form-text card-muted">
                        Removing a photo in Photos won't remove it here — deleting synced photos is
                        deliberately a command-line action, since it can't be undone.
                    </p>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <form method="POST" action="{{ route('admin.icloud.sync', $album) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Sync now</button>
                        </form>

                        <form method="POST" action="{{ route('admin.icloud.update', $album) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="icloud_auto_sync" value="{{ $album->icloud_auto_sync ? 0 : 1 }}">
                            <button type="submit" class="btn btn-tinted btn-sm">
                                {{ $album->icloud_auto_sync ? 'Pause hourly sync' : 'Resume hourly sync' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.icloud.destroy', $album) }}"
                              onsubmit="return confirm('Unlink &quot;{{ $album->name }}&quot; from iCloud? Photos already imported are kept, they just stop updating.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link link-danger btn-sm p-0 border-0">Unlink</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="card p-4">
                <ul class="nav nav-tabs mb-3" id="photoManageTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-order-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-order" type="button" role="tab">
                            Photo Order
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-thumb-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-thumb" type="button" role="tab">
                            Thumbnail
                        </button>
                    </li>
                </ul>

                {{-- Each tab's thumbnails only get a real src once that tab is
                     actually opened (see script below) -- these two tabs used
                     to be separate cards that BOTH rendered an <img> per photo
                     on every page load, fetching every thumbnail twice even
                     though only one is ever used at a time. --}}
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-order" role="tabpanel">
                        <p class="form-text card-muted mb-3">
                            Pinned photos always lead the gallery, in the order below. Everything else falls back to date-taken order.
                        </p>

                        @if ($photos->isEmpty())
                            <p class="card-muted mb-0">No photos uploaded to this album yet.</p>
                        @else
                            <div class="mb-3">
                                <h3 class="h6 card-muted mb-2" style="font-size: 0.75rem;">Pinned</h3>
                                <ul id="pinned-photo-list" class="pinned-photo-list">
                                    @foreach ($pinnedPhotos as $photo)
                                        <li class="pinned-photo-item" draggable="true" data-photo-id="{{ $photo->id }}">
                                            <span class="pinned-photo-handle" title="Drag to reorder">&#x2630;</span>
                                            @if ($photo->thumbnail_path)
                                                <img data-thumb-src="{{ route('photos.thumbnail', $photo) }}" alt="{{ $photo->original_filename }}">
                                            @else
                                                <span class="photo-pick-pending" style="width:40px;height:40px;display:inline-block;border-radius:4px;"></span>
                                            @endif
                                            <span class="pinned-photo-name">{{ $photo->original_filename }}</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary unpin-btn">Unpin</button>
                                        </li>
                                    @endforeach
                                </ul>
                                <p id="pinned-empty-hint" class="card-muted mb-0" style="font-size: 0.85rem; {{ $pinnedPhotos->isEmpty() ? '' : 'display:none;' }}">
                                    No pinned photos yet — pin one below to bring it to the front.
                                </p>
                            </div>

                            <div>
                                <h3 class="h6 card-muted mb-2" style="font-size: 0.75rem;">Other photos (sorted by date taken)</h3>
                                <div id="unpinned-photo-grid" class="photo-pick-grid">
                                    @foreach ($unpinnedPhotos as $photo)
                                        <div class="photo-pick-wrap" data-photo-id="{{ $photo->id }}" data-filename="{{ $photo->original_filename }}" data-thumb="{{ $photo->thumbnail_path ? route('photos.thumbnail', $photo) : '' }}">
                                            @if ($photo->thumbnail_path)
                                                <img data-thumb-src="{{ route('photos.thumbnail', $photo) }}" class="photo-pick" alt="{{ $photo->original_filename }}">
                                            @else
                                                <span class="photo-pick photo-pick-pending" title="Thumbnail still processing"></span>
                                            @endif
                                            <button type="button" class="photo-pin-btn pin-btn" title="Pin to front">Pin</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3 mt-3">
                                <button type="button" id="save-photo-order-btn" class="btn btn-primary btn-sm" data-reorder-url="{{ route('admin.albums.photos.reorder', $album) }}">
                                    Save Order
                                </button>
                                <span id="photo-order-status" class="form-text card-muted mb-0"></span>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="tab-thumb" role="tabpanel">
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
                                                    <img data-thumb-src="{{ route('photos.thumbnail', $photo) }}" class="photo-pick" alt="{{ $photo->original_filename }}">
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
        </div>
    </div>

    <script>
        (function () {
            // "Photo Order" and "Thumbnail" are separate tabs specifically so
            // only one of them ever has its thumbnails actually requested --
            // images start with data-thumb-src (no network request happens)
            // and only get a real src once their tab is opened.
            function loadImagesIn(pane) {
                pane.querySelectorAll('img[data-thumb-src]').forEach((img) => {
                    img.src = img.dataset.thumbSrc;
                    img.removeAttribute('data-thumb-src');
                });
            }

            const orderPane = document.getElementById('tab-order');
            const thumbPane = document.getElementById('tab-thumb');
            const thumbTabBtn = document.getElementById('tab-thumb-btn');

            // "Photo Order" is the default-active tab, so its images load
            // right away; "Thumbnail" only loads once actually opened.
            if (orderPane) {
                loadImagesIn(orderPane);
            }

            if (thumbTabBtn && thumbPane) {
                thumbTabBtn.addEventListener('shown.bs.tab', () => loadImagesIn(thumbPane), { once: true });
            }
        })();

        (function () {
            const pinnedList = document.getElementById('pinned-photo-list');
            const unpinnedGrid = document.getElementById('unpinned-photo-grid');
            const saveBtn = document.getElementById('save-photo-order-btn');
            const statusEl = document.getElementById('photo-order-status');
            const emptyHint = document.getElementById('pinned-empty-hint');
            if (!pinnedList || !unpinnedGrid || !saveBtn) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function toggleEmptyHint() {
                if (emptyHint) {
                    emptyHint.style.display = pinnedList.querySelector('.pinned-photo-item') ? 'none' : '';
                }
            }

            function markDirty() {
                statusEl.textContent = 'Unsaved changes';
            }

            function makePinnedItem(photoId, filename, thumbUrl) {
                const li = document.createElement('li');
                li.className = 'pinned-photo-item';
                li.draggable = true;
                li.dataset.photoId = photoId;
                li.innerHTML =
                    '<span class="pinned-photo-handle" title="Drag to reorder">&#x2630;</span>' +
                    (thumbUrl
                        ? '<img src="' + thumbUrl + '" alt="">'
                        : '<span class="photo-pick-pending" style="width:40px;height:40px;display:inline-block;border-radius:4px;"></span>') +
                    '<span class="pinned-photo-name"></span>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary unpin-btn">Unpin</button>';
                li.querySelector('.pinned-photo-name').textContent = filename;
                if (thumbUrl) li.querySelector('img').alt = filename;
                return li;
            }

            function makeUnpinnedWrap(photoId, filename, thumbUrl) {
                const div = document.createElement('div');
                div.className = 'photo-pick-wrap';
                div.dataset.photoId = photoId;
                div.dataset.filename = filename;
                div.dataset.thumb = thumbUrl || '';
                div.innerHTML =
                    (thumbUrl
                        ? '<img src="' + thumbUrl + '" class="photo-pick" alt="">'
                        : '<span class="photo-pick photo-pick-pending" title="Thumbnail still processing"></span>') +
                    '<button type="button" class="photo-pin-btn pin-btn" title="Pin to front">Pin</button>';
                if (thumbUrl) div.querySelector('img').alt = filename;
                return div;
            }

            unpinnedGrid.addEventListener('click', (event) => {
                const btn = event.target.closest('.pin-btn');
                if (!btn) return;
                const wrap = btn.closest('.photo-pick-wrap');
                const { photoId, filename, thumb } = wrap.dataset;
                wrap.remove();
                pinnedList.appendChild(makePinnedItem(photoId, filename, thumb));
                toggleEmptyHint();
                markDirty();
            });

            pinnedList.addEventListener('click', (event) => {
                const btn = event.target.closest('.unpin-btn');
                if (!btn) return;
                const item = btn.closest('.pinned-photo-item');
                const img = item.querySelector('img');
                const photoId = item.dataset.photoId;
                const filename = item.querySelector('.pinned-photo-name').textContent;
                const thumb = img ? img.getAttribute('src') : '';
                item.remove();
                unpinnedGrid.appendChild(makeUnpinnedWrap(photoId, filename, thumb));
                toggleEmptyHint();
                markDirty();
            });

            let dragged = null;

            pinnedList.addEventListener('dragstart', (event) => {
                dragged = event.target.closest('.pinned-photo-item');
                event.dataTransfer.effectAllowed = 'move';
            });

            pinnedList.addEventListener('dragover', (event) => {
                event.preventDefault();
                const target = event.target.closest('.pinned-photo-item');
                if (!target || target === dragged) return;

                const rect = target.getBoundingClientRect();
                const before = (event.clientY - rect.top) < rect.height / 2;
                pinnedList.insertBefore(dragged, before ? target : target.nextSibling);
            });

            pinnedList.addEventListener('drop', (event) => {
                event.preventDefault();
                markDirty();
            });

            saveBtn.addEventListener('click', () => {
                const photoIds = Array.from(pinnedList.querySelectorAll('.pinned-photo-item[data-photo-id]'))
                    .map((item) => Number(item.dataset.photoId));

                saveBtn.disabled = true;
                statusEl.textContent = 'Saving…';

                fetch(saveBtn.dataset.reorderUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ photo_ids: photoIds }),
                })
                    .then((response) => {
                        if (!response.ok) throw new Error('Save failed');
                        statusEl.textContent = 'Saved.';
                    })
                    .catch(() => {
                        statusEl.textContent = 'Save failed — try again.';
                    })
                    .finally(() => {
                        saveBtn.disabled = false;
                    });
            });
        })();
    </script>
</x-app-layout>
