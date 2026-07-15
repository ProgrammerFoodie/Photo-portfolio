<x-app-layout>
    <x-slot name="header">
        <h1>Create Album</h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card p-4">

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.albums.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Title</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  class="form-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="date_taken" class="form-label">Date of capture</label>
                        <input type="date" name="date_taken" id="date_taken"
                               value="{{ old('date_taken', now()->toDateString()) }}"
                               class="form-control">
                        <div class="form-text card-muted">Defaults to today — change it if the photos are from a different date.</div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Place</label>
                        <input type="text" name="location" id="location" value="{{ old('location') }}"
                               placeholder="e.g. Riga, Latvia"
                               class="form-control">
                    </div>

                    <div class="mb-4">
                        <label for="parent_id" class="form-label">Parent Album (optional)</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">— None (top-level album) —</option>
                            @foreach ($parentOptions as $option)
                                <option value="{{ $option->id }}" @selected(old('parent_id') == $option->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text card-muted">Pick this to create a sub-album instead of a top-level album.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Create Album
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
