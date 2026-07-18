<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Download;
use App\Models\Photo;
use App\Support\Theme;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $albums = Album::query()
            ->whereNull('parent_id')
            ->withCount('photos')
            ->with('cover')
            ->orderBy('sort_order')
            ->orderByDesc('date_taken')
            ->get();

        return view('home', [
            'albums' => $albums,
            'totalPhotos' => Photo::count(),
            'totalAlbums' => Album::count(),
            'totalDownloads' => Download::count(),
            'heroPhotos' => Theme::is('version-2')
                ? Photo::where('status', 'ready')->whereNotNull('thumbnail_path')->inRandomOrder()->limit(7)->get()
                : collect(),
        ]);
    }
}
