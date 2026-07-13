<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'date_taken',
        'location',
        'cover_photo_id',
        'sort_order',
    ];

    protected $casts = [
        'date_taken' => 'date',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Album::class, 'parent_id')->orderBy('sort_order');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    protected static function booted(): void
    {
        // Delete child photos through Eloquent (not just the DB cascade) so
        // Photo's own `deleting` event fires and cleans up files on disk.
        static::deleting(function (Album $album) {
            $album->photos->each(fn (Photo $photo) => $photo->delete());
        });
    }

    public function isSubAlbum(): bool
    {
        return $this->parent_id !== null;
    }

    public function canHaveSubAlbums(): bool
    {
        // Enforces exactly 2 levels: only top-level albums may have children.
        return $this->parent_id === null;
    }
}