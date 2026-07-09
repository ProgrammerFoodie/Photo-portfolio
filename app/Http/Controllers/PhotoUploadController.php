<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinalizeUploadRequest;
use App\Http\Requests\StoreChunkRequest;
use App\Models\Album;
use App\Services\PhotoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PhotoUploadController extends Controller
{
    public function __construct(private PhotoUploadService $uploadService)
    {
    }

    public function showUploadForm(): View
    {
        $albums = Album::orderBy('name')->get();

        return view('admin.photos.upload', ['albums' => $albums]);
    }

    public function storeChunk(StoreChunkRequest $request): JsonResponse
    {
        $this->uploadService->storeChunk(
            chunk: $request->file('chunk'),
            uploadId: $request->string('upload_id')->toString(),
            chunkIndex: $request->integer('chunk_index'),
        );

        return response()->json([
            'status' => 'chunk_received',
            'chunk_index' => $request->integer('chunk_index'),
        ]);
    }

    public function finalize(FinalizeUploadRequest $request): JsonResponse
    {
        $album = Album::findOrFail($request->integer('album_id'));

        $photo = $this->uploadService->finalizeUpload(
            uploadId: $request->string('upload_id')->toString(),
            originalFilename: $request->string('original_filename')->toString(),
            album: $album,
            expectedTotalChunks: $request->integer('total_chunks'),
        );

        return response()->json([
            'status' => 'uploaded',
            'photo_id' => $photo->id,
        ]);
    }
}