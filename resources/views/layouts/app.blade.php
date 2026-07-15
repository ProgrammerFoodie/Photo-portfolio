<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\Setting::get('site_title') }} &middot; Admin</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

        <style>
            :root, [data-bs-theme="dark"] {
                /* Same brand palette as the public site: Jet Black base,
                   Vibrant Coral accent, Silver-derived muted tones. */
                --bg: #122932;
                --bg-elevated: #1b333d;
                --bg-sunken: #0f2129;
                --border: rgba(192, 192, 192, 0.16);
                --text-muted: rgba(192, 192, 192, 0.7);
                --accent: #f5f5f5;
                --brand: #ff5154;
                --brand-rgb: 255, 81, 84;
                --brand-hover: #ff6e71;
                --bg-elevated-2: #24404c;
                --accent2: #6f73d2;
                --accent3: #754f44;

                --bs-body-bg: var(--bg);
                --bs-body-color: var(--accent);
                --bs-border-color: var(--border);
                --bs-primary: var(--brand);
                --bs-primary-rgb: var(--brand-rgb);
                --bs-link-color: var(--brand);
                --bs-link-hover-color: var(--brand-hover);
                --bs-emphasis-color: var(--accent);
            }

            body {
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                font-weight: 300;
            }

            .admin-navbar {
                background-color: rgba(18, 41, 50, 0.9);
                backdrop-filter: blur(8px);
                border-bottom: 1px solid var(--border);
            }

            .admin-navbar .navbar-brand {
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                font-size: 1.05rem;
                color: var(--accent);
            }

            .admin-navbar .nav-link {
                color: var(--text-muted);
                font-size: 0.9rem;
            }

            .admin-navbar .nav-link:hover,
            .admin-navbar .nav-link.active {
                color: var(--accent);
            }

            .page-header {
                border-bottom: 1px solid var(--border);
                padding: 1.75rem 0;
            }

            .page-header h1 {
                font-size: 1.4rem;
                font-weight: 600;
                margin: 0;
            }

            .card {
                background-color: var(--bg-elevated);
                border: 1px solid var(--border);
            }

            .card-muted {
                color: var(--text-muted);
            }

            .table {
                --bs-table-bg: var(--bg-elevated);
                --bs-table-border-color: var(--border);
                --bs-table-hover-bg: var(--bg-elevated-2);
                vertical-align: middle;
            }

            .form-control, .form-select {
                background-color: var(--bg-sunken);
                border-color: var(--border);
                color: var(--accent);
            }

            .form-control:focus, .form-select:focus {
                background-color: var(--bg-sunken);
                color: var(--accent);
                border-color: var(--brand);
                box-shadow: 0 0 0 0.25rem rgba(var(--brand-rgb), 0.25);
            }

            .form-control::placeholder {
                color: rgba(192, 192, 192, 0.35);
            }

            .form-label {
                font-size: 0.85rem;
                color: var(--text-muted);
                margin-bottom: 0.35rem;
            }

            .form-text {
                font-size: 0.78rem;
            }

            .btn-primary {
                background-color: var(--brand);
                border-color: var(--brand);
            }

            .btn-primary:hover {
                background-color: var(--brand-hover);
                border-color: var(--brand-hover);
            }

            .btn-outline-light {
                --bs-btn-color: var(--accent);
                --bs-btn-border-color: var(--border);
                --bs-btn-hover-bg: var(--bg-elevated-2);
                --bs-btn-hover-border-color: var(--border);
            }

            .stat-card {
                border-radius: 0.5rem;
                padding: 1.25rem 1.4rem;
                height: 100%;
            }

            .stat-card .stat-label {
                font-size: 0.8rem;
                color: var(--text-muted);
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .stat-card .stat-value {
                font-size: 1.85rem;
                font-weight: 600;
                color: var(--accent);
                margin-top: 0.15rem;
            }

            .stat-card .stat-icon {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(var(--brand-rgb), 0.15);
                color: var(--brand);
            }

            .chart-card {
                border-radius: 0.5rem;
                padding: 1.25rem 1.4rem;
            }

            .chart-card h6 {
                color: var(--text-muted);
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                margin-bottom: 1rem;
            }

            .photo-pick-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 1.25rem;
                padding-top: 0.25rem;
            }

            .photo-pick-wrap {
                position: relative;
            }

            .photo-pick-btn {
                border: 0;
                background: none;
                padding: 0;
                cursor: pointer;
                display: block;
            }

            .photo-pick,
            .photo-pick-pending {
                width: 32px;
                height: 32px;
                object-fit: cover;
                border-radius: 4px;
                border: 2px solid var(--border);
                display: block;
                background-color: var(--bg-elevated-2);
                transition: transform 0.15s ease, border-color 0.15s ease;
            }

            .photo-pick-wrap:hover .photo-pick,
            .photo-pick-wrap:hover .photo-pick-pending {
                transform: scale(5);
                position: relative;
                z-index: 5;
                border-color: var(--brand);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            }

            .photo-pick-wrap.is-cover .photo-pick,
            .photo-pick-wrap.is-cover .photo-pick-pending {
                border-color: var(--accent2);
            }

            .photo-pick-badge {
                position: absolute;
                top: -8px;
                right: -8px;
                background: var(--accent2);
                color: #ffffff;
                font-size: 0.6rem;
                font-weight: 600;
                padding: 0.05rem 0.3rem;
                border-radius: 3px;
                z-index: 6;
                pointer-events: none;
            }
        </style>
    </head>
    <body>
        @include('layouts.navigation')

        @isset($header)
            <div class="page-header">
                <div class="container-xl">
                    {{ $header }}
                </div>
            </div>
        @endisset

        <main class="py-4">
            <div class="container-xl">
                {{ $slot }}
            </div>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
