<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Apple's stable per-photo identity. Lengths are bounded because
            // this column is part of a composite index: under utf8mb4 a
            // varchar(255) plus a bigint crowds InnoDB's key limit. Real
            // guids are ~36 chars.
            $table->string('icloud_photo_guid', 64)->nullable();

            // Checksum of the derivative we downloaded, so a photo re-edited
            // upstream can be detected later without re-downloading everything.
            $table->string('icloud_checksum', 128)->nullable();

            // Makes sync idempotent: a re-run can never duplicate a photo, no
            // matter how a previous run died partway through. Photos uploaded
            // through the site keep a null guid, and both MySQL and SQLite
            // treat nulls as distinct in a unique index, so any number of
            // uploaded photos can coexist here.
            $table->unique(['album_id', 'icloud_photo_guid'], 'photos_album_icloud_guid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropUnique('photos_album_icloud_guid_unique');
            $table->dropColumn(['icloud_photo_guid', 'icloud_checksum']);
        });
    }
};
