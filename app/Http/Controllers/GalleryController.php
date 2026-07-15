<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Download;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class GalleryController extends Controller
{
    public function show(Album $album): View
    {
        $album->load([
            // Sort by when the photo was actually taken (falling back to
            // upload time for photos with no EXIF date), then by filename
            // as a stable tiebreaker for photos taken in the same second.
            'photos' => fn ($query) => $query->where('status', 'ready')
                ->orderByRaw('COALESCE(captured_at, created_at) ASC')
                ->orderBy('original_filename'),
            'children' => fn ($query) => $query->withCount('photos')->with('cover'),
        ]);

        return view('albums.show', ['album' => $album]);
    }

    /**
     * Photo bytes at a given path never change after upload, so these are
     * safe to cache aggressively — lets the browser skip re-downloading
     * (and skip revalidation entirely, thanks to "immutable") when the same
     * photo is viewed again, e.g. navigating back to it in the lightbox.
     */
    private const CACHE_HEADERS = ['Cache-Control' => 'public, max-age=604800, immutable'];

    public function viewPhoto(Album $album, Photo $photo): StreamedResponse
    {
        return Storage::disk('local')->response($photo->original_path, null, self::CACHE_HEADERS);
    }

    /**
     * Thumbnails live on the private "local" disk, so they can't be linked
     * via Storage::url() (that path requires a signed URL). Serve them
     * through our own route instead.
     */
    public function thumbnail(Photo $photo): StreamedResponse
    {
        abort_unless($photo->thumbnail_path, 404);

        return Storage::disk('local')->response($photo->thumbnail_path, null, self::CACHE_HEADERS);
    }

    public function downloadPhoto(Request $request, Album $album, Photo $photo): StreamedResponse
    {
        Download::create([
            'album_id' => $album->id,
            'photo_id' => $photo->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Storage::disk('local')->download($photo->original_path, $photo->original_filename);
    }

    public function downloadSelected(Request $request, Album $album): BinaryFileResponse
    {
        $request->validate([
            'photo_ids' => ['required', 'array'],
            'photo_ids.*' => ['integer'],
        ]);

        $photos = $album->photos()->whereIn('id', $request->input('photo_ids'))->get();

        abort_if($photos->isEmpty(), 404);

        $zipDir = storage_path('app/private/tmp');
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zipPath = $zipDir . '/' . Str::uuid() . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        foreach ($photos as $photo) {
            $zip->addFile(Storage::disk('local')->path($photo->original_path), $photo->original_filename);

            Download::create([
                'album_id' => $album->id,
                'photo_id' => $photo->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $zip->close();

        return response()
            ->download($zipPath, Str::slug($album->name) . '-photos.zip')
            ->deleteFileAfterSend(true);
    }
}
