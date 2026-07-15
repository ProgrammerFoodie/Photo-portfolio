<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1>Message</h1>
            <a href="{{ route('admin.messages.index') }}" class="link-secondary">&larr; Back to Messages</a>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card p-4">
                <div class="mb-3">
                    <div class="card-muted small text-uppercase" style="letter-spacing: 0.03em;">From</div>
                    <div class="fw-medium">{{ $message->name }}</div>
                    <div class="card-muted">
                        <a href="mailto:{{ $message->email }}" class="link-primary">{{ $message->email }}</a>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="card-muted small text-uppercase" style="letter-spacing: 0.03em;">Received</div>
                    <div>{{ $message->created_at->format('Y-m-d H:i') }}</div>
                </div>

                <div class="mb-4">
                    <div class="card-muted small text-uppercase mb-1" style="letter-spacing: 0.03em;">Message</div>
                    <p class="mb-0">{!! nl2br(e($message->message)) !!}</p>
                </div>

                <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                      onsubmit="return confirm('Delete this message? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-light btn-sm">Delete Message</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
