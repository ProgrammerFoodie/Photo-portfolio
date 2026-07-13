<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
use App\Http\Controllers\PhotoUploadController;
use App\Http\Controllers\AlbumController;

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/upload', [PhotoUploadController::class, 'showUploadForm'])->name('upload.form');
    Route::post('/upload-chunk', [PhotoUploadController::class, 'storeChunk'])->name('upload.chunk');
    Route::post('/upload-finalize', [PhotoUploadController::class, 'finalize'])->name('upload.finalize');

    Route::get('/albums', [AlbumController::class, 'index'])->name('admin.albums.index');
    Route::get('/albums/create', [AlbumController::class, 'create'])->name('admin.albums.create');
    Route::post('/albums', [AlbumController::class, 'store'])->name('admin.albums.store');
    Route::get('/albums/{album}/edit', [AlbumController::class, 'edit'])->name('admin.albums.edit');
    Route::put('/albums/{album}', [AlbumController::class, 'update'])->name('admin.albums.update');
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy'])->name('admin.albums.destroy');
});