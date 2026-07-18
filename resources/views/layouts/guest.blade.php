<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\Setting::get('site_title') }}</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <style>
            :root, [data-bs-theme="dark"] {
                --bg: #122932;
                --bg-elevated: #1b333d;
                --bg-sunken: #0f2129;
                --border: rgba(192, 192, 192, 0.16);
                --text-muted: rgba(192, 192, 192, 0.7);
                --accent: #f5f5f5;
                --brand: #ff5154;
                --brand-rgb: 255, 81, 84;
                --brand-hover: #ff6e71;

                --bs-body-bg: var(--bg);
                --bs-body-color: var(--accent);
                --bs-border-color: var(--border);
                --bs-primary: var(--brand);
                --bs-primary-rgb: var(--brand-rgb);
                --bs-link-color: var(--brand);
                --bs-link-hover-color: var(--brand-hover);
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Inter', system-ui, sans-serif;
                font-weight: 400;
                -webkit-font-smoothing: antialiased;
                background-color: var(--bg);
                color: var(--accent);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
            }

            .auth-brand {
                font-weight: 700;
                font-size: 1.1rem;
                letter-spacing: -0.01em;
                color: var(--accent);
                text-decoration: none;
                margin-bottom: 1.5rem;
            }

            .auth-card {
                max-width: 26rem;
                width: 100%;
                background: var(--bg-elevated);
                border-radius: 1rem;
                padding: 2rem;
            }

            .btn {
                border-radius: 999px;
                font-weight: 500;
                transition: transform 0.12s ease, background-color 0.15s ease, border-color 0.15s ease;
            }

            .btn:active {
                transform: scale(0.96);
            }

            .btn-primary {
                background-color: var(--brand);
                border-color: var(--brand);
            }

            .btn-primary:hover,
            .btn-primary:active {
                background-color: var(--brand-hover);
                border-color: var(--brand-hover);
            }

            .form-control {
                background-color: var(--bg-sunken);
                border-color: var(--border);
                color: var(--accent);
            }

            .form-control:focus {
                background-color: var(--bg-sunken);
                color: var(--accent);
                border-color: var(--brand);
                box-shadow: 0 0 0 0.25rem rgba(var(--brand-rgb), 0.25);
            }

            .form-label {
                font-size: 0.85rem;
                color: var(--text-muted);
            }

            .card-muted {
                color: var(--text-muted);
            }

            .form-check-input:checked {
                background-color: var(--brand);
                border-color: var(--brand);
            }
        </style>
    </head>
    <body>
        <a href="/" class="auth-brand">{{ \App\Models\Setting::get('site_title') }}</a>

        <div class="auth-card">
            {{ $slot }}
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
