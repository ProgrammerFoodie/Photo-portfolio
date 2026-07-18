<?php

use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/profile-cover', [SettingsController::class, 'coverImage'])->name('profile.cover');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])
    ->middleware('throttle:5,1')
    ->name('contact.submit');

Route::get('/photos/{photo}/thumbnail', [GalleryController::class, 'thumbnail'])->name('photos.thumbnail');

Route::prefix('albums/{album:slug}')->group(function () {
    Route::get('/', [GalleryController::class, 'show'])->name('albums.show');
    Route::post('/download', [GalleryController::class, 'downloadSelected'])->name('albums.downloadSelected');

    Route::scopeBindings()->group(function () {
        Route::get('/photos/{photo}/view', [GalleryController::class, 'viewPhoto'])->name('photos.view');
        Route::get('/photos/{photo}/download', [GalleryController::class, 'downloadPhoto'])->name('photos.download');
    });
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
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\UserController;

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/upload', [PhotoUploadController::class, 'showUploadForm'])->name('upload.form');
    Route::post('/upload-chunk', [PhotoUploadController::class, 'storeChunk'])->name('upload.chunk');
    Route::post('/upload-finalize', [PhotoUploadController::class, 'finalize'])->name('upload.finalize');

    Route::get('/albums', [AlbumController::class, 'index'])->name('admin.albums.index');
    Route::get('/albums/create', [AlbumController::class, 'create'])->name('admin.albums.create');
    Route::post('/albums', [AlbumController::class, 'store'])->name('admin.albums.store');
    Route::get('/albums/{album}/edit', [AlbumController::class, 'edit'])->name('admin.albums.edit');
    Route::put('/albums/{album}', [AlbumController::class, 'update'])->name('admin.albums.update');
    Route::patch('/albums/{album}/cover', [AlbumController::class, 'setCover'])->name('admin.albums.setCover');
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy'])->name('admin.albums.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');

    Route::get('/messages', [ContactMessageController::class, 'index'])->name('admin.messages.index');
    Route::get('/messages/{message}', [ContactMessageController::class, 'show'])->name('admin.messages.show');
    Route::delete('/messages/{message}', [ContactMessageController::class, 'destroy'])->name('admin.messages.destroy');

    Route::middleware('can:manage-users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });
});