<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumRequest;
use App\Models\Album;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function create(): View
    {
        return view('admin.albums.create');
    }

    public function store(StoreAlbumRequest $request): RedirectResponse
    {
        $album = Album::create([
            'name' => $request->string('name')->toString(),
            'slug' => Str::slug($request->string('name')->toString()) . '-' . Str::random(6),
            'description' => $request->input('description'),
            'date_taken' => $request->input('date_taken'),
            'location' => $request->input('location'),
        ]);

        return redirect()
            ->route('admin.albums.create')
            ->with('status', "Album \"{$album->name}\" created successfully.");
    }
}