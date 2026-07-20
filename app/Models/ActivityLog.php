<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'description', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an action taken by the currently authenticated user. Silently
     * no-ops if called outside a logged-in request (shouldn't normally
     * happen, since every logged action lives behind auth middleware).
     */
    public static function log(string $action, string $description): void
    {
        if (!Auth::check()) {
            return;
        }

        static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
