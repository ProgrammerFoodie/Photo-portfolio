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
        'icloud_token',
        'icloud_stream_ctag',
        'icloud_last_synced_at',
        'icloud_sync_status',
        'icloud_sync_error',
        'icloud_auto_sync',
        'icloud_prune',
    ];

    protected $casts = [
        'date_taken' => 'date',
        'icloud_last_synced_at' => 'datetime',
        'icloud_auto_sync' => 'boolean',
        'icloud_prune' => 'boolean',
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

    /**
     * Make $photo this album's cover if it hasn't got one yet — and, when this
     * is a subalbum, the parent's cover too, so parent albums whose only photos
     * live in a subalbum don't stay coverless.
     *
     * Conditional UPDATEs rather than a read-then-write: two photos landing at
     * once (uploads and iCloud sync both dispatch concurrently) would otherwise
     * race and the second could clobber the first.
     *
     * Shared by PhotoUploadService and the iCloud sync so both paths assign
     * covers identically.
     */
    public function assignCoverIfMissing(Photo $photo): void
    {
        static::whereKey($this->id)
            ->whereNull('cover_photo_id')
            ->update(['cover_photo_id' => $photo->id]);

        if ($this->parent_id !== null) {
            static::whereKey($this->parent_id)
                ->whereNull('cover_photo_id')
                ->update(['cover_photo_id' => $photo->id]);
        }
    }
}