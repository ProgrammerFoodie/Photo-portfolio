<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
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

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User \"{$user->username}\" created successfully.");
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

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User \"{$username}\" deleted.");
    }
}
