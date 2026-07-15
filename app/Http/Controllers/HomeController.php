<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Download;
use App\Models\Photo;
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
        ]);
    }
}
