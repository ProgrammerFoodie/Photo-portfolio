<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Download;
use App\Models\Photo;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class GalleryController extends Controller
{
    public function __construct(private readonly DashboardStatsService $stats)
    {
    }

    public function show(Album $album): View
    {
        $album->load([
            // Pinned photos (sort_order > 0) lead the gallery in the order
            // the admin arranged them. Everything else (sort_order == 0)
            // falls back to when the photo was actually taken (or upload
            // time, for photos with no EXIF date), with filename as a
            // stable tiebreaker for photos taken in the same second.
            'photos' => fn ($query) => $query->where('status', 'ready')
                ->orderByRaw('CASE WHEN sort_order > 0 THEN 0 ELSE 1 END ASC')
                ->orderBy('sort_order')
                ->orderByRaw('COALESCE(captured_at, created_at) ASC')
                ->orderBy('original_filename'),
            'children' => fn ($query) => $query->withCount('photos')->with('cover'),
        ]);

        [$prevAlbum, $nextAlbum] = $this->siblingAlbums($album);

        return view('albums.show', [
            'album' => $album,
            'prevAlbum' => $prevAlbum,
            'nextAlbum' => $nextAlbum,
        ]);
    }

    /**
     * Previous/next album for the header's prev/next controls, using the same
     * ordering as the home page's album grid (sort_order, then date_taken
     * desc). Only defined for top-level albums -- a sub-album has no "next
     * sub-album" concept in this design, so both come back null and the view
     * renders disabled arrows.
     *
     * @return array{0: ?Album, 1: ?Album}
     */
    private function siblingAlbums(Album $album): array
    {
        if ($album->isSubAlbum()) {
            return [null, null];
        }

        $topLevelAlbums = Album::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderByDesc('date_taken')
            ->get(['id', 'slug', 'name', 'sort_order', 'date_taken']);

        $currentIndex = $topLevelAlbums->search(fn (Album $a) => $a->is($album));

        if ($currentIndex === false) {
            return [null, null];
        }

        return [
            $currentIndex > 0 ? $topLevelAlbums[$currentIndex - 1] : null,
            $currentIndex < $topLevelAlbums->count() - 1 ? $topLevelAlbums[$currentIndex + 1] : null,
        ];
    }

    /**
     * Photo bytes at a given path never change after upload, so these are
     * safe to cache aggressively — lets the browser skip re-downloading
     * (and skip revalidation entirely, thanks to "immutable") when the same
     * photo is viewed again, e.g. navigating back to it in the lightbox.
     */
    private const CACHE_HEADERS = [
        'Cache-Control' => 'public, max-age=604800, immutable',
        // Stop a browser from re-interpreting stored bytes as HTML/script.
        'X-Content-Type-Options' => 'nosniff',
    ];

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

        $this->stats->clearCache();

        return Storage::disk('local')->download($photo->original_path, $photo->original_filename);
    }

    /**
     * Give a zip entry a name no other entry in this archive is using, by
     * suffixing " (2)", " (3)" and so on before the extension — the same
     * convention a desktop file manager uses for a name clash.
     *
     * @param  array<string, true>  $usedNames  seen names, by reference
     */
    private function uniqueEntryName(string $filename, array &$usedNames): string
    {
        $key = mb_strtolower($filename);

        if (!isset($usedNames[$key])) {
            $usedNames[$key] = true;

            return $filename;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $stem = pathinfo($filename, PATHINFO_FILENAME);
        $suffix = $extension === '' ? '' : '.' . $extension;

        for ($i = 2; ; $i++) {
            $candidate = "{$stem} ({$i}){$suffix}";
            $candidateKey = mb_strtolower($candidate);

            if (!isset($usedNames[$candidateKey])) {
                $usedNames[$candidateKey] = true;

                return $candidate;
            }
        }
    }

    public function downloadSelected(Request $request, Album $album): BinaryFileResponse
    {
        $request->validate([
            // Capped because the archive is built synchronously inside the
            // request: with a small php-fpm worker pool, a handful of
            // uncapped concurrent zips can exhaust every worker.
            'photo_ids' => ['required', 'array', 'max:100'],
            'photo_ids.*' => ['integer'],
        ]);

        $photos = $album->photos()->whereIn('id', $request->input('photo_ids'))->get();

        abort_if($photos->isEmpty(), 404);

        // Resolved through the disk rather than storage_path(): it lands in the
        // same place in production (the local disk is rooted at app/private)
        // but follows Storage::fake() under test.
        $disk = Storage::disk('local');
        $disk->makeDirectory('tmp');

        $zipPath = $disk->path('tmp/' . Str::uuid() . '.zip');

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            abort(500, 'Could not create archive.');
        }

        // Filenames are not unique — photos synced from iCloud carry their real
        // camera names, and a burst or a re-added shot can produce two photos
        // called IMG_3884.JPG. Adding both under one name makes the archive
        // ambiguous and most extractors keep only the last, so the visitor
        // silently receives fewer files than they picked.
        $usedNames = [];

        foreach ($photos as $photo) {
            $name = $this->uniqueEntryName($photo->original_filename, $usedNames);

            $zip->addFile(Storage::disk('local')->path($photo->original_path), $name);
        }

        $zip->close();

        // Record downloads only once the archive is actually built, so a
        // failed zip doesn't inflate the per-photo download counts.
        foreach ($photos as $photo) {
            Download::create([
                'album_id' => $album->id,
                'photo_id' => $photo->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $this->stats->clearCache();

        return response()
            ->download($zipPath, Str::slug($album->name) . '-photos.zip')
            ->deleteFileAfterSend(true);
    }
}
