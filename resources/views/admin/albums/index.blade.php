<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Albums') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('admin.albums.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">
                    + New Album
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Parent</th>
                            <th class="px-4 py-3">Photos</th>
                            <th class="px-4 py-3">Size</th>
                            <th class="px-4 py-3">Downloads</th>
                            <th class="px-4 py-3">Date Taken</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($albums as $album)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $album->name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $album->parent?->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $album->photos_count }}</td>
                                <td class="px-4 py-3">{{ $album->size_human }}</td>
                                <td class="px-4 py-3">{{ $album->downloads_count }}</td>
                                <td class="px-4 py-3">{{ optional($album->date_taken)->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.albums.edit', $album) }}" class="text-indigo-600 hover:underline">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.albums.destroy', $album) }}"
                                              onsubmit="return confirm('Delete &quot;{{ $album->name }}&quot; and all its photos? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-400">No albums yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $albums->links() }}
            </div>

        </div>
    </div>
</x-app-layout>