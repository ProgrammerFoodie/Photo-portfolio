<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'username' => $request->string('username')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        ActivityLog::log('user.created', "Created user \"{$user->username}\"");

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User \"{$user->username}\" created successfully.");
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'viewedUser' => $user,
            'logs' => $user->activityLogs()->paginate(25),
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === 1) {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'The super-admin account cannot be deleted.');
        }

        $username = $user->username;
        $user->delete();

        ActivityLog::log('user.deleted', "Deleted user \"{$username}\"");

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User \"{$username}\" deleted.");
    }
}
