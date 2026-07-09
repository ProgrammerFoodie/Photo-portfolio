<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload Photos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="mb-4">
                    <label for="albumSelect" class="block text-sm font-medium text-gray-700">Album</label>
                    <select id="albumSelect" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach ($albums as $album)
                            <option value="{{ $album->id }}">{{ $album->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="photoInput" class="block text-sm font-medium text-gray-700">Photo (.jpg / .jpeg only)</label>
                    <input type="file" id="photoInput" accept="image/jpeg" class="mt-1 block w-full">
                </div>

                <button id="uploadBtn" type="button" class="bg-indigo-600 text-white px-4 py-2 rounded-md">
                    Upload
                </button>

                <p id="status" class="mt-4 text-sm text-gray-600"></p>

            </div>
        </div>
    </div>

    <script>
        const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB per chunk

        document.getElementById('uploadBtn').addEventListener('click', async () => {
            const fileInput = document.getElementById('photoInput');
            const file = fileInput.files[0];

            if (!file) {
                alert('Please select a JPEG file first.');
                return;
            }

            const albumId = document.getElementById('albumSelect').value;
            const uploadId = crypto.randomUUID();
            const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
            const statusEl = document.getElementById('status');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            statusEl.textContent = `Uploading chunk 0 / ${totalChunks}...`;

            for (let i = 0; i < totalChunks; i++) {
                const start = i * CHUNK_SIZE;
                const end = Math.min(start + CHUNK_SIZE, file.size);
                const chunkBlob = file.slice(start, end);

                const formData = new FormData();
                formData.append('upload_id', uploadId);
                formData.append('chunk_index', i);
                formData.append('total_chunks', totalChunks);
                formData.append('chunk', chunkBlob);

                const response = await fetch('/admin/upload-chunk', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorBody = await response.text();
                    statusEl.textContent = `Failed on chunk ${i}: HTTP ${response.status}`;
                    console.error(errorBody);
                    return;
                }

                statusEl.textContent = `Uploading chunk ${i + 1} / ${totalChunks}...`;
            }

            statusEl.textContent = 'Finalizing...';

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

            const result = await finalizeResponse.json();

            if (finalizeResponse.ok) {
                statusEl.textContent = `✅ Upload complete! Photo ID: ${result.photo_id}`;
            } else {
                statusEl.textContent = `❌ Finalize failed: ${JSON.stringify(result)}`;
            }
        });
    </script>
</x-app-layout>