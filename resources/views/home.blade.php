<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Gallery') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #0b0c0f;
            --bg-elevated: #131417;
            --border: #232428;
            --text-muted: #9a9ba1;
            --accent: #e8e9ec;
        }

        body {
            background-color: var(--bg);
            color: #e8e9ec;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-weight: 300;
        }

        a { text-decoration: none; }

        .navbar {
            background-color: rgba(11, 12, 15, 0.85);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
        }

        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 1.05rem;
        }

        .nav-link {
            color: var(--text-muted) !important;
            font-size: 0.9rem;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--accent) !important;
        }

        .hero {
            padding: 5.5rem 0 3rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .hero h1 {
            font-weight: 600;
            font-size: clamp(1.8rem, 4vw, 3rem);
            letter-spacing: -0.01em;
        }

        .hero p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 40rem;
            margin: 0.75rem auto 0;
        }

        .album-card {
            position: relative;
            background-color: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.2s ease, border-color 0.2s ease;
            height: 100%;
        }

        .album-card:hover {
            transform: translateY(-4px);
            border-color: #3a3b40;
        }

        @media (min-width: 992px) {
            .album-card-body {
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                padding: 1rem;
                background: linear-gradient(to top, rgba(0, 0, 0, 0.88), rgba(0, 0, 0, 0.35) 55%, transparent);
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .album-card:hover .album-card-body {
                opacity: 1;
            }
        }

        .album-thumb {
            aspect-ratio: 4 / 3;
            width: 100%;
            object-fit: cover;
            display: block;
            background-color: #1a1b1e;
        }

        .album-thumb-placeholder {
            aspect-ratio: 4 / 3;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1c1d21, #101114);
            color: #4a4b50;
        }

        .album-card-body {
            padding: 0.9rem 1rem 1.1rem;
        }

        .album-title {
            color: var(--accent);
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 0.15rem;
        }

        .album-meta {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        footer {
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.85rem;
            padding: 2rem 0;
        }

        .empty-state {
            color: var(--text-muted);
            padding: 5rem 0;
            text-align: center;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-md sticky-top py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">{{ config('app.name', 'Gallery') }}</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <ul class="navbar-nav gap-md-4">
                    <li class="nav-item"><a class="nav-link active" href="{{ route('home') }}">Albums</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <h1>Photo Albums</h1>
            <p>A collection of moments, sorted and stored.</p>
        </div>
    </header>

    <main class="container py-5">
        @if ($albums->isEmpty())
            <div class="empty-state">
                <p class="mb-0">No albums to show yet.</p>
            </div>
        @else
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
                @foreach ($albums as $album)
                    <div class="col">
                        <a href="#" class="album-card d-block">
                            @if ($album->cover?->thumbnail_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::url($album->cover->thumbnail_path) }}"
                                    alt="{{ $album->name }}"
                                    class="album-thumb"
                                    loading="lazy"
                                >
                            @else
                                <div class="album-thumb-placeholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                        <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="album-card-body">
                                <div class="album-title">{{ $album->name }}</div>
                                <div class="album-meta">
                                    {{ $album->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $album->photos_count) }}
                                    @if ($album->date_taken)
                                        &middot; {{ $album->date_taken->format('M Y') }}
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    <footer>
        <div class="container text-center">
            &copy; {{ now()->year }} {{ config('app.name', 'Gallery') }}
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
