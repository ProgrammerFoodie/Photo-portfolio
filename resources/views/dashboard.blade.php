<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Total Photos</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($stats['total_photos']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Total Size on Disk</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $stats['total_size_human'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Albums / Sub-albums</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($stats['total_albums']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Total Downloads</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($stats['total_downloads']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Top-level Albums</p>
                    <p class="text-xl font-semibold text-gray-900 mt-1">{{ number_format($stats['total_top_level_albums']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Sub-albums</p>
                    <p class="text-xl font-semibold text-gray-900 mt-1">{{ number_format($stats['total_sub_albums']) }}</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-3">
                <a href="{{ route('admin.albums.index') }}" class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm">
                    Manage Albums
                </a>
                <a href="{{ route('admin.albums.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">
                    + New Album
                </a>
                <a href="{{ route('upload.form') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm">
                    Upload Photos
                </a>
            </div>

        </div>
    </div>
</x-app-layout>