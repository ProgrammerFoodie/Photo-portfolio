<x-app-layout>
    <x-slot name="header">
        <h1>Settings</h1>
    </x-slot>

    @php
        // Jump straight to whichever tab has a validation error, so failed
        // input isn't left hidden behind another tab after a failed save.
        $tabFields = [
            'header' => ['cover_image', 'profile_handle', 'profile_display_name', 'profile_bio'],
            'about' => ['about_title', 'about_body'],
            'contact' => ['contact_title', 'contact_body'],
            'social' => ['social_links'],
        ];
        $activeTab = 'general';
        foreach ($tabFields as $tab => $fields) {
            foreach ($fields as $field) {
                if ($errors->has($field) || $errors->has($field . '.*')) {
                    $activeTab = $tab;
                    break 2;
                }
            }
        }
    @endphp

    @if (session('status'))
        <div class="alert alert-success mb-4">
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

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card p-4">

                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" id="tab-general-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                            General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'header' ? 'active' : '' }}" id="tab-header-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-header" type="button" role="tab">
                            Homepage Header
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'about' ? 'active' : '' }}" id="tab-about-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-about" type="button" role="tab">
                            About Page
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'contact' ? 'active' : '' }}" id="tab-contact-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-contact" type="button" role="tab">
                            Contact Page
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'social' ? 'active' : '' }}" id="tab-social-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-social" type="button" role="tab">
                            Social Links
                        </button>
                    </li>
                </ul>

                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="tab-content" id="settingsTabsContent">

                        <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="tab-general" role="tabpanel">
                            <div class="mb-3">
                                <label for="site_title" class="form-label">Site Title</label>
                                <input type="text" name="site_title" id="site_title"
                                       value="{{ old('site_title', $settings['site_title']) }}"
                                       class="form-control" required>
                                <div class="form-text card-muted">Shown in the browser tab and the top navigation bar.</div>
                            </div>

                            <div class="mb-3">
                                <label for="footer_text" class="form-label">Footer Text</label>
                                <input type="text" name="footer_text" id="footer_text"
                                       value="{{ old('footer_text', $settings['footer_text']) }}"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'header' ? 'show active' : '' }}" id="tab-header" role="tabpanel">
                            <div class="form-text card-muted mb-3">
                                Shown as the Instagram-style header at the top of your homepage (and on the
                                About/Contact page banners, using the same cover image).
                            </div>

                            <div class="mb-3">
                                <label for="cover_image" class="form-label">Cover Image</label>
                                @if ($settings['profile_cover_path'])
                                    <div class="mb-2">
                                        <img src="{{ route('profile.cover') }}" alt="Current cover"
                                             style="max-width: 100%; max-height: 10rem; border-radius: 0.5rem; display: block;">
                                    </div>
                                @endif
                                <input type="file" name="cover_image" id="cover_image" accept="image/*" class="form-control">
                                <div class="form-text card-muted">Used as the background of the homepage header. Leave blank to keep the current image.</div>
                            </div>

                            <div class="mb-3">
                                <label for="profile_handle" class="form-label">Handle</label>
                                <input type="text" name="profile_handle" id="profile_handle"
                                       value="{{ old('profile_handle', $settings['profile_handle']) }}"
                                       class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="profile_display_name" class="form-label">Display Name</label>
                                <input type="text" name="profile_display_name" id="profile_display_name"
                                       value="{{ old('profile_display_name', $settings['profile_display_name']) }}"
                                       class="form-control">
                            </div>

                            <div class="mb-3">
                                <label for="profile_bio" class="form-label">Bio</label>
                                <textarea name="profile_bio" id="profile_bio" rows="2"
                                          class="form-control">{{ old('profile_bio', $settings['profile_bio']) }}</textarea>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'about' ? 'show active' : '' }}" id="tab-about" role="tabpanel">
                            <div class="mb-3">
                                <label for="about_title" class="form-label">About Page Title</label>
                                <input type="text" name="about_title" id="about_title"
                                       value="{{ old('about_title', $settings['about_title']) }}"
                                       class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="about_body" class="form-label">About Page Content</label>
                                <textarea name="about_body" id="about_body" rows="8"
                                          class="form-control">{{ old('about_body', $settings['about_body']) }}</textarea>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'contact' ? 'show active' : '' }}" id="tab-contact" role="tabpanel">
                            <div class="mb-3">
                                <label for="contact_title" class="form-label">Contact Page Title</label>
                                <input type="text" name="contact_title" id="contact_title"
                                       value="{{ old('contact_title', $settings['contact_title']) }}"
                                       class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="contact_body" class="form-label">Contact Page Content</label>
                                <textarea name="contact_body" id="contact_body" rows="8"
                                          class="form-control">{{ old('contact_body', $settings['contact_body']) }}</textarea>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'social' ? 'show active' : '' }}" id="tab-social" role="tabpanel">
                            <div class="form-text card-muted mb-2">
                                Shown as buttons on the Contact page. Add one row per platform — label plus
                                its full URL. For a WhatsApp click-to-chat link, use a label like "WhatsApp"
                                with a URL like <code>https://wa.me/15551234567</code>.
                            </div>

                            <div id="socialLinksRows">
                                @foreach (old('social_links', $socialLinks) as $i => $row)
                                    <div class="row g-2 mb-2 social-link-row">
                                        <div class="col-4">
                                            <input type="text" name="social_links[{{ $i }}][label]"
                                                   value="{{ $row['label'] ?? '' }}"
                                                   class="form-control form-control-sm" placeholder="Label (e.g. Instagram)">
                                        </div>
                                        <div class="col-7">
                                            <input type="text" name="social_links[{{ $i }}][url]"
                                                   value="{{ $row['url'] ?? '' }}"
                                                   class="form-control form-control-sm" placeholder="https://...">
                                        </div>
                                        <div class="col-1 d-flex align-items-center">
                                            <button type="button" class="btn btn-sm btn-outline-light remove-social-row" aria-label="Remove">&times;</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" id="addSocialRow" class="btn btn-sm btn-tinted">+ Add Link</button>

                            <template id="socialRowTemplate">
                                <div class="row g-2 mb-2 social-link-row">
                                    <div class="col-4">
                                        <input type="text" name="social_links[__INDEX__][label]"
                                               class="form-control form-control-sm" placeholder="Label (e.g. Instagram)">
                                    </div>
                                    <div class="col-7">
                                        <input type="text" name="social_links[__INDEX__][url]"
                                               class="form-control form-control-sm" placeholder="https://...">
                                    </div>
                                    <div class="col-1 d-flex align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-light remove-social-row" aria-label="Remove">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>

                    <hr class="my-4">

                    <button type="submit" class="btn btn-primary">
                        Save Settings
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
        (function () {
            const rowsContainer = document.getElementById('socialLinksRows');
            const addButton = document.getElementById('addSocialRow');
            const template = document.getElementById('socialRowTemplate');
            let nextIndex = rowsContainer.querySelectorAll('.social-link-row').length;

            addButton.addEventListener('click', () => {
                const html = template.innerHTML.replaceAll('__INDEX__', nextIndex);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                rowsContainer.appendChild(wrapper.firstElementChild);
                nextIndex++;
            });

            rowsContainer.addEventListener('click', (e) => {
                const button = e.target.closest('.remove-social-row');
                if (button) {
                    button.closest('.social-link-row').remove();
                }
            });
        })();
    </script>
</x-app-layout>
