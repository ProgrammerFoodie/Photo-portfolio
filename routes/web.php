<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
use App\Http\Controllers\PhotoUploadController;

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/upload', [PhotoUploadController::class, 'showUploadForm'])->name('upload.form');
    Route::post('/upload-chunk', [PhotoUploadController::class, 'storeChunk'])->name('upload.chunk');
    Route::post('/upload-finalize', [PhotoUploadController::class, 'finalize'])->name('upload.finalize');
});