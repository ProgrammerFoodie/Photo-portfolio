<x-app-layout>
    <x-slot name="header">
        <h2>Profile</h2>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4 mb-4">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="card p-4 mb-4">
                @include('profile.partials.update-password-form')
            </div>

            <div class="card p-4">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
