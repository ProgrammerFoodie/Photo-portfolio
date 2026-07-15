<x-app-layout>
    <x-slot name="header">
        <h1>Messages</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr class="card-muted">
                        <th class="ps-4">From</th>
                        <th>Message</th>
                        <th>Received</th>
                        <th class="pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr class="{{ $message->read_at === null ? 'fw-medium' : '' }}">
                            <td class="ps-4">
                                @if ($message->read_at === null)
                                    <span class="badge text-bg-primary me-1">New</span>
                                @endif
                                {{ $message->name }}
                                <div class="card-muted small fw-normal">{{ $message->email }}</div>
                            </td>
                            <td class="card-muted">
                                {{ \Illuminate\Support\Str::limit($message->message, 80) }}
                            </td>
                            <td class="card-muted">{{ $message->created_at->format('Y-m-d H:i') }}</td>
                            <td class="pe-4">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="{{ route('admin.messages.show', $message) }}" class="link-primary">
                                        View
                                    </a>
                                    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                                          onsubmit="return confirm('Delete this message? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link link-danger p-0 border-0 align-baseline">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center card-muted py-5">No messages yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $messages->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
