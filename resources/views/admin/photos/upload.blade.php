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
        const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB per chunk
        const MAX_CONCURRENT_FILES = 3;
        const MAX_CHUNK_RETRIES = 1;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const folderInput = document.getElementById('folderInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const fileCountEl = document.getElementById('fileCount');
        const fileListEl = document.getElementById('fileList');
        const overallBar = document.getElementById('overallBar');
        const overallStatus = document.getElementById('overallStatus');

        let selectedFiles = [];

        folderInput.addEventListener('change', () => {
            selectedFiles = Array.from(folderInput.files).filter(f =>
                /\.(jpe?g)$/i.test(f.name)
            );

            fileCountEl.textContent = `${selectedFiles.length} JPEG file(s) found (others in folder ignored).`;
            uploadBtn.disabled = selectedFiles.length === 0;

            fileListEl.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const li = document.createElement('li');
                li.id = `file-row-${index}`;
                li.className = 'list-group-item d-flex justify-content-between align-items-center bg-transparent';
                li.innerHTML = `<span>${file.name}</span><span id="file-status-${index}" class="card-muted">Queued</span>`;
                fileListEl.appendChild(li);
            });
        });

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
            const statusEl = document.getElementById(`file-status-${index}`);
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

                    statusEl.textContent = `${i + 1}/${totalChunks} chunks`;
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

                statusEl.textContent = '✅ Done';
                statusEl.className = 'text-success';
                return { file: file.name, success: true };
            } catch (err) {
                statusEl.textContent = `❌ Failed (${err.message})`;
                statusEl.className = 'text-danger';
                return { file: file.name, success: false, error: err.message };
            }
        }

        async function runWithConcurrencyLimit(files, albumId, limit) {
            const results = [];
            let nextIndex = 0;
            let completed = 0;

            async function worker() {
                while (nextIndex < files.length) {
                    const currentIndex = nextIndex++;
                    const result = await uploadSingleFile(files[currentIndex], currentIndex, albumId);
                    results.push(result);
                    completed++;

                    const percent = Math.round((completed / files.length) * 100);
                    overallBar.style.width = `${percent}%`;
                    overallStatus.textContent = `${completed} / ${files.length} files processed`;
                }
            }

            const workers = Array.from({ length: Math.min(limit, files.length) }, () => worker());
            await Promise.all(workers);

            return results;
        }

        uploadBtn.addEventListener('click', async () => {
            uploadBtn.disabled = true;
            folderInput.disabled = true;
            const albumId = document.getElementById('albumSelect').value;

            overallStatus.textContent = `Starting upload of ${selectedFiles.length} files...`;

            const results = await runWithConcurrencyLimit(selectedFiles, albumId, MAX_CONCURRENT_FILES);

            const failed = results.filter(r => !r.success);
            const succeeded = results.filter(r => r.success);

            overallStatus.textContent = failed.length === 0
                ? `✅ All ${succeeded.length} files uploaded successfully!`
                : `⚠️ ${succeeded.length} succeeded, ${failed.length} failed. See list above for details.`;

            uploadBtn.disabled = false;
            folderInput.disabled = false;
        });
    </script>
</x-app-layout>
