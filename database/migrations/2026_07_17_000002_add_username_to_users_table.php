<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill any pre-existing accounts, since this column didn't exist
        // before. foodie@f@l.h is the site owner (id 1, our super-admin);
        // everyone else gets a username derived from their email.
        $overrides = [
            'f@l.h' => 'foodie',
        ];

        DB::table('users')->whereNull('username')->orderBy('id')->get(['id', 'email'])->each(function ($user) use ($overrides) {
            $base = $overrides[$user->email] ?? Str::slug(Str::before($user->email, '@'), '');
            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.$suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
