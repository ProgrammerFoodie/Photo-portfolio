<x-app-layout>
    <x-slot name="header">
        <h1>Upload Photos</h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card p-4">

                <div class="mb-3">
                    <label for="albumSelect" class="form-label">Album</label>
                    <select id="albumSelect" class="form-select">
                        @foreach ($albums as $album)
                            <option value="{{ $album->id }}">{{ $album->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="folderInput" class="form-label">
                        Select photos (.jpg / .jpeg only) — hold Cmd (Mac) or Ctrl (Windows) to pick multiple
                    </label>
                    <input type="file" id="folderInput" accept="image/jpeg" multiple class="form-control">
                </div>

                <p id="fileCount" class="mb-3 form-text card-muted"></p>

                <button id="uploadBtn" type="button" class="btn btn-primary" disabled>
                    Upload All
                </button>

                <p class="mt-2 form-text card-muted">
                    You can browse other admin pages while this uploads — it keeps running in the background.
                    Just don't submit a form elsewhere (like saving Settings) or refresh the page, since that will interrupt it.
                </p>

                <div class="mt-4">
                    <div class="progress" style="height: 0.75rem;">
                        <div id="overallBar" class="progress-bar" style="width: 0%"></div>
                    </div>
                    <p id="overallStatus" class="mt-2 form-text card-muted"></p>
                </div>

                <ul id="fileList" class="list-group list-group-flush mt-4" style="max-height: 20rem; overflow-y: auto;"></ul>

            </div>
        </div>
    </div>

    <script>
        (function () {
            const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB per chunk
            const MAX_CONCURRENT_FILES = 3;
            const MAX_CHUNK_RETRIES = 1;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Survives AJAX-driven admin navigation (see public/js/admin-nav.js)
            // so an upload keeps running, and its progress keeps rendering,
            // even while the user is "on" a different admin page. A real
            // page reload still resets this, since that destroys all JS state.
            window.__adminUpload = window.__adminUpload || {
                active: false,
                files: [], // [{ name, status, className }]
                completed: 0,
                total: 0,
            };

            const state = window.__adminUpload;

            function renderNavStatus() {
                const pill = document.getElementById('nav-upload-status');
                if (!pill) return;

                if (state.active) {
                    pill.style.display = '';
                    pill.querySelector('span').textContent = `Uploading ${state.completed}/${state.total}…`;
                } else {
                    pill.style.display = 'none';
                }
            }

            function statusPriority(status) {
                if (/^\d+\/\d+ chunks$/.test(status)) return 0; // being uploaded
                if (status === 'Queued') return 1;
                if (status === '✅ Done') return 2;
                return 3; // failed / anything else
            }

            function renderFileList() {
                const fileListEl = document.getElementById('fileList');
                if (!fileListEl) return;

                // Keep each row's id tied to its original index (setFileStatus
                // looks elements up by that), but display them sorted:
                // uploading first, then queued, then done/failed.
                const order = state.files
                    .map((f, index) => ({ f, index }))
                    .sort((a, b) => statusPriority(a.f.status) - statusPriority(b.f.status));

                fileListEl.innerHTML = '';
                order.forEach(({ f, index }) => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center bg-transparent';
                    li.innerHTML = `<span>${f.name}</span><span id="file-status-${index}" class="${f.className}">${f.status}</span>`;
                    fileListEl.appendChild(li);
                });
            }

            function renderProgress() {
                const overallBar = document.getElementById('overallBar');
                const overallStatus = document.getElementById('overallStatus');
                const percent = state.total ? Math.round((state.completed / state.total) * 100) : 0;

                if (overallBar) {
                    overallBar.style.width = `${percent}%`;
                }
                if (overallStatus && state.active) {
                    overallStatus.textContent = `${state.completed} / ${state.total} files processed`;
                }

                renderNavStatus();
            }

            function setFileStatus(index, status, className) {
                state.files[index].status = status;
                state.files[index].className = className;
                renderFileList();
            }

            async function uploadChunkWithRetry(formData, attemptsLeft) {
                try {
                    const response = await fetch('/admin/upload-chunk', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    return true;
                } catch (err) {
                    if (attemptsLeft > 0) {
                        return uploadChunkWithRetry(formData, attemptsLeft - 1);
                    }
                    throw err;
                }
            }

            async function uploadSingleFile(file, index, albumId) {
                const uploadId = crypto.randomUUID();
                const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

                try {
                    for (let i = 0; i < totalChunks; i++) {
                        const start = i * CHUNK_SIZE;
                        const end = Math.min(start + CHUNK_SIZE, file.size);
                        const chunkBlob = file.slice(start, end);

                        const formData = new FormData();
                        formData.append('upload_id', uploadId);
                        formData.append('chunk_index', i);
                        formData.append('total_chunks', totalChunks);
                        formData.append('chunk', chunkBlob);

                        await uploadChunkWithRetry(formData, MAX_CHUNK_RETRIES);
                        setFileStatus(index, `${i + 1}/${totalChunks} chunks`, 'card-muted');
                    }

                    const finalizeResponse = await fetch('/admin/upload-finalize', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            upload_id: uploadId,
                            original_filename: file.name,
                            album_id: albumId,
                            total_chunks: totalChunks,
                        }),
                    });

                    if (!finalizeResponse.ok) {
                        throw new Error(`Finalize HTTP ${finalizeResponse.status}`);
                    }

                    setFileStatus(index, '✅ Done', 'text-success');
                    return { success: true };
                } catch (err) {
                    setFileStatus(index, `❌ Failed (${err.message})`, 'text-danger');
                    return { success: false };
                }
            }

            async function runUpload(files, albumId, limit) {
                let nextIndex = 0;

                async function worker() {
                    while (nextIndex < files.length) {
                        const currentIndex = nextIndex++;
                        await uploadSingleFile(files[currentIndex], currentIndex, albumId);
                        state.completed++;
                        renderProgress();
                    }
                }

                const workers = Array.from({ length: Math.min(limit, files.length) }, () => worker());
                await Promise.all(workers);

                const failedCount = state.files.filter(f => f.className === 'text-danger').length;
                const succeededCount = state.files.length - failedCount;

                state.active = false;
                renderNavStatus();

                const overallStatus = document.getElementById('overallStatus');
                if (overallStatus) {
                    overallStatus.textContent = failedCount === 0
                        ? `✅ All ${succeededCount} files uploaded successfully!`
                        : `⚠️ ${succeededCount} succeeded, ${failedCount} failed. See list above for details.`;
                }

                const uploadBtn = document.getElementById('uploadBtn');
                const folderInput = document.getElementById('folderInput');
                if (uploadBtn) uploadBtn.disabled = false;
                if (folderInput) folderInput.disabled = false;
            }

            function initPage() {
                const folderInput = document.getElementById('folderInput');
                const uploadBtn = document.getElementById('uploadBtn');
                const fileCountEl = document.getElementById('fileCount');

                if (!folderInput || !uploadBtn) {
                    return;
                }

                if (state.active) {
                    // An upload is already running in the background (started
                    // before we navigated here) -- reflect its live state
                    // instead of showing a blank picker.
                    folderInput.disabled = true;
                    uploadBtn.disabled = true;
                    fileCountEl.textContent = `Upload in progress: ${state.files.length} file(s).`;
                    renderFileList();
                    renderProgress();
                    return;
                }

                let selectedFiles = [];

                folderInput.addEventListener('change', () => {
                    selectedFiles = Array.from(folderInput.files).filter(f =>
                        /\.(jpe?g)$/i.test(f.name)
                    );

                    fileCountEl.textContent = `${selectedFiles.length} JPEG file(s) found (others in folder ignored).`;
                    uploadBtn.disabled = selectedFiles.length === 0;

                    state.files = selectedFiles.map(f => ({ name: f.name, status: 'Queued', className: 'card-muted' }));
                    state.completed = 0;
                    state.total = selectedFiles.length;
                    renderFileList();
                });

                uploadBtn.addEventListener('click', () => {
                    uploadBtn.disabled = true;
                    folderInput.disabled = true;
                    const albumId = document.getElementById('albumSelect').value;

                    state.active = true;
                    state.completed = 0;
                    state.total = selectedFiles.length;

                    const overallStatus = document.getElementById('overallStatus');
                    if (overallStatus) {
                        overallStatus.textContent = `Starting upload of ${selectedFiles.length} files...`;
                    }
                    renderNavStatus();

                    runUpload(selectedFiles, albumId, MAX_CONCURRENT_FILES);
                });
            }

            initPage();
        })();
    </script>
</x-app-layout>
