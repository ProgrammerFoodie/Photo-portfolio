<x-app-layout>
    <x-slot name="header">
        <h1>Settings</h1>
    </x-slot>

    @php
        // Jump straight to whichever tab has a validation error, so failed
        // input isn't left hidden behind another tab after a failed save.
        $tabFields = [
            'header' => ['cover_image', 'profile_handle', 'profile_display_name', 'profile_bio', 'profile_header_height'],
            'about' => ['about_title', 'about_body'],
            'contact' => ['contact_title', 'contact_body'],
            'social' => ['social_links'],
            'theme' => ['theme'],
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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'theme' ? 'active' : '' }}" id="tab-theme-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-theme" type="button" role="tab">
                            Theme
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
                                       class="form-control">
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
                                <label for="profile_header_height" class="form-label">Header Height (px)</label>
                                <input type="number" name="profile_header_height" id="profile_header_height"
                                       value="{{ old('profile_header_height', $settings['profile_header_height']) }}"
                                       min="120" max="800" class="form-control" style="max-width: 10rem;">
                                <div class="form-text card-muted">
                                    Height of the header banner on the homepage and About/Contact pages. The preview below
                                    matches this exactly.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="cover_image" class="form-label">Cover Image</label>
                                @if ($settings['profile_cover_path'])
                                    {{-- Full-bleed preview: breaks out of the centered form card to span
                                         the full viewport width, exactly like the real page header. Uses
                                         the identical CSS (background-size: cover + background-position-y)
                                         so the crop matches the live site pixel-for-pixel at this browser
                                         width. --}}
                                    <div style="width: 100vw; position: relative; left: 50%; margin-left: -50vw;">
                                        <div id="coverRepositionFrame"
                                             data-cover-url="{{ route('profile.cover') }}"
                                             style="position: relative; width: 100%; height: {{ $settings['profile_header_height'] }}px; overflow: hidden; background-image: url('{{ route('profile.cover') }}'); background-size: cover; background-position: center {{ $settings['profile_cover_position_y'] }}%; cursor: grab; touch-action: none; user-select: none;">
                                            <div style="position: absolute; inset: 0; pointer-events: none; background: linear-gradient(180deg, rgba(20, 14, 10, 0.45) 0%, rgba(20, 14, 10, 0.8) 100%);"></div>
                                            <div class="container" style="position: absolute; inset: 0; display: flex; align-items: flex-end; padding-bottom: 1.75rem; pointer-events: none;">
                                                <div>
                                                    <div style="color: var(--brand); font-size: 0.72rem; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase; margin-bottom: 0.4rem;">&#10022; {{ $settings['site_title'] }}</div>
                                                    <div style="color: #fff; font-weight: 800; font-size: clamp(2rem, 5vw, 3rem); line-height: 1.05; letter-spacing: -0.02em;">{{ $settings['profile_handle'] ?: 'Preview' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text card-muted mt-2">
                                        Live preview of your site header at this browser width &mdash; drag the image
                                        up/down to choose which part shows. What you see here is what visitors see.
                                    </div>
                                    <input type="hidden" name="profile_cover_position_y" id="profile_cover_position_y"
                                           value="{{ old('profile_cover_position_y', $settings['profile_cover_position_y']) }}">
                                @endif
                                <input type="file" name="cover_image" id="cover_image" accept="image/*" class="form-control mt-2">
                                <div class="form-text card-muted">Uploading a new image resets its position to centered. Leave blank to keep the current image.</div>
                            </div>

                            <div class="mb-3">
                                <label for="profile_handle" class="form-label">Handle</label>
                                <input type="text" name="profile_handle" id="profile_handle"
                                       value="{{ old('profile_handle', $settings['profile_handle']) }}"
                                       class="form-control">
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
                                       class="form-control">
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
                                       class="form-control">
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

                        <div class="tab-pane fade {{ $activeTab === 'theme' ? 'show active' : '' }}" id="tab-theme" role="tabpanel">
                            <div class="mb-3">
                                <label for="theme" class="form-label">Active Theme</label>
                                <select name="theme" id="theme" class="form-select">
                                    @foreach (config('themes') as $slug => $label)
                                        <option value="{{ $slug }}" @selected(old('theme', $settings['theme']) === $slug)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text card-muted">Controls which look the public site uses.</div>
                            </div>
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

        (function () {
            const frame = document.getElementById('coverRepositionFrame');
            if (!frame) {
                return;
            }

            const hiddenInput = document.getElementById('profile_cover_position_y');
            const heightInput = document.getElementById('profile_header_height');

            // The preview is rendered with the exact same CSS as the real page
            // header (background-size: cover + background-position-y), so the
            // crop is identical at a given browser width. Dragging only changes
            // the vertical position percentage. We need the image's natural
            // dimensions to know how many pixels of drag map to a full 0->100%.
            let naturalW = 0;
            let naturalH = 0;
            const probe = new Image();
            probe.onload = () => { naturalW = probe.naturalWidth; naturalH = probe.naturalHeight; };
            probe.src = frame.dataset.coverUrl;

            // With background-size: cover, the image is scaled up so it fully
            // covers the frame, then the excess height (the overflow) is what
            // background-position-y slides through. This overflow, in on-screen
            // pixels, is exactly how far a drag can move from 0% to 100%.
            function overflowPx() {
                if (!naturalW || !naturalH) {
                    return 0;
                }
                const w = frame.clientWidth;
                const h = frame.clientHeight;
                const scale = Math.max(w / naturalW, h / naturalH);
                return Math.max(0, naturalH * scale - h);
            }

            function applyPercent(percent) {
                const clamped = Math.min(100, Math.max(0, percent));
                hiddenInput.value = Math.round(clamped);
                frame.style.backgroundPosition = `center ${clamped}%`;
            }

            let dragging = false;
            let startClientY = 0;
            let startPercent = 50;

            function pointerY(e) {
                return e.touches ? e.touches[0].clientY : e.clientY;
            }

            function pointerDown(e) {
                dragging = true;
                startClientY = pointerY(e);
                startPercent = parseInt(hiddenInput.value, 10) || 50;
                frame.style.cursor = 'grabbing';
                e.preventDefault();
            }

            function pointerMove(e) {
                if (!dragging) {
                    return;
                }
                const overflow = overflowPx();
                if (overflow <= 0) {
                    return;
                }
                // Dragging up (negative delta) reveals lower content, which is a
                // higher position percentage -- hence the minus sign.
                const delta = pointerY(e) - startClientY;
                applyPercent(startPercent - (delta / overflow) * 100);
                e.preventDefault();
            }

            function pointerUp() {
                if (dragging) {
                    dragging = false;
                    frame.style.cursor = 'grab';
                }
            }

            frame.addEventListener('mousedown', pointerDown);
            frame.addEventListener('touchstart', pointerDown, { passive: false });
            window.addEventListener('mousemove', pointerMove);
            window.addEventListener('touchmove', pointerMove, { passive: false });
            window.addEventListener('mouseup', pointerUp);
            window.addEventListener('touchend', pointerUp);

            // Live height changes: background-position % auto-recomputes against
            // the new dimensions, so just resizing the frame is enough.
            if (heightInput) {
                heightInput.addEventListener('input', () => {
                    const height = parseInt(heightInput.value, 10);
                    if (height >= 120 && height <= 800) {
                        frame.style.height = `${height}px`;
                    }
                });
            }
        })();
    </script>
</x-app-layout>
