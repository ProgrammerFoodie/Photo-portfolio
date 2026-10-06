<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            // The token from a public share link (everything after the "#" in
            // https://www.icloud.com/sharedalbum/#B0xxxxxxxxx). Unique so the
            // same shared album can't be linked to two albums and sync them
            // into each other.
            $table->string('icloud_token', 128)->nullable()->unique();

            // Apple's version marker for the album. Unchanged ctag means
            // nothing changed upstream, so a sync run costs one request.
            $table->string('icloud_stream_ctag', 128)->nullable();

            $table->timestamp('icloud_last_synced_at')->nullable();
            $table->string('icloud_sync_status', 16)->nullable();
            $table->text('icloud_sync_error')->nullable();

            $table->boolean('icloud_auto_sync')->default(true);

            // Deleting photos that vanished upstream is destructive and there
            // are no backups, so it stays off until a given album's prune
            // reports have been observed to be correct.
            $table->boolean('icloud_prune')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropUnique(['icloud_token']);
            $table->dropColumn([
                'icloud_token',
                'icloud_stream_ctag',
                'icloud_last_synced_at',
                'icloud_sync_status',
                'icloud_sync_error',
                'icloud_auto_sync',
                'icloud_prune',
            ]);
        });
    }
};
