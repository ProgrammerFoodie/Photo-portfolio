<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Album') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-md text-sm">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.albums.update', $album) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $album->name) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $album->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="date_taken" class="block text-sm font-medium text-gray-700">Date of capture</label>
                        <input type="date" name="date_taken" id="date_taken"
                               value="{{ old('date_taken', optional($album->date_taken)->format('Y-m-d')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div class="mb-4">
                        <label for="location" class="block text-sm font-medium text-gray-700">Place</label>
                        <input type="text" name="location" id="location" value="{{ old('location', $album->location) }}"
                               placeholder="e.g. Riga, Latvia"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    @if ($album->parent_id === null)
                        <div class="mb-6">
                            <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Album</label>
                            <select name="parent_id" id="parent_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">— None (top-level album) —</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}" @selected(old('parent_id', $album->parent_id) == $option->id)>
                                        {{ $option->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Only top-level albums can be chosen as a parent (albums are two levels deep).</p>
                        </div>
                    @else
                        <input type="hidden" name="parent_id" value="{{ $album->parent_id }}">
                        <p class="mb-6 text-xs text-gray-400">
                            This is a sub-album of <span class="font-medium">{{ $album->parent?->name }}</span>.
                        </p>
                    @endif

                    <div class="flex items-center gap-3">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md">
                            Save Changes
                        </button>
                        <a href="{{ route('admin.albums.index') }}" class="text-sm text-gray-500 hover:underline">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>