<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'original_filename',
        'original_path',
        'thumbnail_path',
        'filesize',
        'width',
        'height',
        'status',
        'sort_order',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Photo $photo) {
            $disk = Storage::disk('local');

            if ($photo->original_path) {
                $disk->delete($photo->original_path);
            }

            if ($photo->thumbnail_path) {
                $disk->delete($photo->thumbnail_path);
            }
        });
    }
}