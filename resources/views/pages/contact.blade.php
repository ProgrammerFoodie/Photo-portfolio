<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} &middot; {{ \App\Models\Setting::get('site_title') }}</title>

    @include('partials.site-styles')
</head>
<body>

    @include('partials.site-nav')

    @php
        $coverPath = \App\Models\Setting::get('profile_cover_path');
    @endphp
    <header class="profile-header" @if ($coverPath) style="background-image: url('{{ route('profile.cover') }}');" @endif>
        <div class="container">
            <h1 class="page-hero-title mb-0">{{ $title }}</h1>
        </div>
    </header>

    @include('partials.site-tabs')

    <main class="container py-4" style="min-height: 40vh;">
        <p class="text-body-secondary" style="max-width: 45rem;">{!! nl2br(e($body)) !!}</p>

        @if (!empty($socialLinks))
            <div class="d-flex flex-wrap gap-2 mb-4">
                @foreach ($socialLinks as $link)
                    <a href="{{ $link['url'] }}" class="btn btn-tinted btn-sm" target="_blank" rel="noopener noreferrer">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="row">
            <div class="col-lg-7">
                <div class="card p-4" style="max-width: 32rem;">
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

                    <form method="POST" action="{{ route('contact.submit') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                   class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea name="message" id="message" rows="5"
                                      class="form-control" required>{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.site-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
