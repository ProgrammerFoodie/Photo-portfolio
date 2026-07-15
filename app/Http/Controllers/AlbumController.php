<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumRequest;
use App\Http\Requests\UpdateAlbumRequest;
use App\Models\Album;
use App\Services\DashboardStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $stats,
    ) {
    }

    /**
     * Table list view: every album with photo count, size on disk,
     * download count, and a link to edit.
     */
    public function index(): View
    {
        $albums = Album::query()
            ->withCount(['photos', 'downloads'])
            ->withSum('photos as photos_size', 'filesize')
            ->with('parent:id,name')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Attach display-ready fields without touching the raw models,
        // so the pagination links/meta stay intact for the Blade paginator.
        $albums->through(fn (Album $album) => tap($album, function (Album $album) {
            $album->size_human = Number::fileSize($album->photos_size ?? 0, precision: 2);
        }));

        return view('admin.albums.index', ['albums' => $albums]);
    }

    public function create(): View
    {
        return view('admin.albums.create', [
            'parentOptions' => Album::query()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreAlbumRequest $request): RedirectResponse
    {
        $album = Album::create([
            'parent_id' => $request->input('parent_id'),
            'name' => $request->string('name')->toString(),
            'slug' => Str::slug($request->string('name')->toString()) . '-' . Str::random(6),
            'description' => $request->input('description'),
            'date_taken' => $request->input('date_taken') ?: now()->toDateString(),
            'location' => $request->input('location'),
        ]);

        $this->stats->clearCache();

        return redirect()
            ->route('admin.albums.create')
            ->with('status', "Album \"{$album->name}\" created successfully.");
    }

    public function edit(Album $album): View
    {
        return view('admin.albums.edit', [
            'album' => $album,
            'photos' => $album->photos()->orderBy('sort_order')->get(),
            'parentOptions' => Album::query()
                ->whereNull('parent_id')
                ->where('id', '!=', $album->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function setCover(Request $request, Album $album): RedirectResponse
    {
        $request->validate([
            'photo_id' => ['required', 'integer', 'exists:photos,id'],
        ]);

        $photo = $album->photos()->findOrFail($request->integer('photo_id'));

        $album->update(['cover_photo_id' => $photo->id]);

        return redirect()
            ->route('admin.albums.edit', $album)
            ->with('status', 'Thumbnail updated.');
    }

    public function update(UpdateAlbumRequest $request, Album $album): RedirectResponse
    {
        $album->update([
            'parent_id' => $request->input('parent_id'),
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'date_taken' => $request->input('date_taken'),
            'location' => $request->input('location'),
        ]);

        $this->stats->clearCache();

        return redirect()
            ->route('admin.albums.index')
            ->with('status', "Album \"{$album->name}\" updated successfully.");
    }

    public function destroy(Album $album): RedirectResponse
    {
        $name = $album->name;
        $album->delete();

        $this->stats->clearCache();

        return redirect()
            ->route('admin.albums.index')
            ->with('status', "Album \"{$name}\" deleted.");
    }
}