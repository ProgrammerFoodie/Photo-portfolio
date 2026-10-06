<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Accounts flagged is_admin may create/delete users. Driven by a column
        // rather than a hardcoded id so the ability survives user 1 being
        // deleted, and so a second admin can be promoted without a code change.
        // `is_admin` is deliberately NOT mass-assignable (see User::$fillable)
        // so it can't be set through a normal profile/registration form.
        Gate::define('manage-users', fn (User $user): bool => (bool) $user->is_admin);
    }
}
