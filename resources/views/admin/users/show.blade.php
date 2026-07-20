<x-app-layout>
    <x-slot name="header">
        <h1>{{ $viewedUser->name }}</h1>
    </x-slot>

    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="link-primary">&larr; All users</a>
    </div>

    <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
        <div class="col">
            <div class="card stat-card">
                <div class="stat-label">Username</div>
                <div class="stat-value" style="font-size: 1.2rem;">{{ $viewedUser->username }}</div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="stat-label">Email</div>
                <div class="stat-value" style="font-size: 1.2rem;">{{ $viewedUser->email }}</div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="stat-label">Last Login</div>
                @if ($viewedUser->last_login_at)
                    <div class="stat-value" style="font-size: 1.2rem;">{{ $viewedUser->last_login_at->format('Y-m-d H:i') }}</div>
                    <div class="card-muted small">{{ $viewedUser->last_login_at->diffForHumans() }}</div>
                @else
                    <div class="card-muted">Never logged in</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="p-4 pb-2">
            <h6 class="card-muted text-uppercase mb-0" style="font-size: 0.8rem; letter-spacing: 0.03em;">Activity Log</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr class="card-muted">
                        <th class="ps-4">Action</th>
                        <th class="pe-4">When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="ps-4">{{ $log->description }}</td>
                            <td class="pe-4 card-muted">{{ $log->created_at->format('Y-m-d H:i') }} &middot; {{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center card-muted py-5">No activity recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $logs->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
