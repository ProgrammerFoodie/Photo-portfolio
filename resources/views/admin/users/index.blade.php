<x-app-layout>
    <x-slot name="header">
        <h1>Users</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Create User</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr class="card-muted">
                        <th class="ps-4">Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th class="pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('admin.users.show', $user) }}" class="link-primary">{{ $user->name }}</a>
                                @if ($user->id === 1)
                                    <span class="badge text-bg-secondary ms-1">Super Admin</span>
                                @endif
                            </td>
                            <td class="card-muted">{{ $user->username }}</td>
                            <td class="card-muted">{{ $user->email }}</td>
                            <td class="pe-4">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="{{ route('admin.users.show', $user) }}" class="link-primary">View</a>
                                    @if ($user->id !== 1)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link link-danger p-0 border-0 align-baseline">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center card-muted py-5">No users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
