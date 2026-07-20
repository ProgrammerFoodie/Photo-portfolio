<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        /* Brand palette: Jet Black base, Vibrant Coral as the single
           interactive accent (buttons/links/active states), Silver for
           muted text/borders. Medium Slate Blue and Mauve Bark are used
           sparingly as secondary accents (see .is-cover, chart colors)
           rather than mixed into every interactive element, to keep the
           interface reading as one clear accent color. */
        --bg: #122932;
        --bg-elevated: #1b333d;
        --bg-elevated-2: #24404c;
        --border: rgba(192, 192, 192, 0.16);
        --text-muted: rgba(192, 192, 192, 0.7);
        --text-tertiary: rgba(192, 192, 192, 0.4);
        --accent: #f5f5f5;
        --brand: #ff5154;
        --brand-rgb: 255, 81, 84;
        --brand-hover: #ff6e71;
        --accent2: #6f73d2;
        --accent2-rgb: 111, 115, 210;
        --accent3: #754f44;

        --bs-body-bg: var(--bg);
        --bs-body-color: var(--accent);
        --bs-border-color: var(--border);
        --bs-primary: var(--brand);
        --bs-primary-rgb: var(--brand-rgb);
        --bs-link-color: var(--brand);
        --bs-link-hover-color: var(--brand-hover);
    }

    body {
        background-color: var(--bg);
        color: var(--accent);
        font-family: -apple-system, BlinkMacSystemFont, 'Inter', system-ui, sans-serif;
        font-weight: 400;
        -webkit-font-smoothing: antialiased;
    }

    a { text-decoration: none; }

    .navbar {
        background-color: rgba(20, 21, 24, 0.72);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--accent);
        letter-spacing: -0.01em;
    }

    .nav-link-cta {
        color: var(--accent) !important;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 999px;
        padding: 0.32rem 0.9rem !important;
        font-weight: 600;
        transition: background-color 0.15s ease;
    }

    .nav-link-cta:hover {
        background-color: rgba(255, 255, 255, 0.16);
        color: var(--accent) !important;
    }

    .btn {
        border-radius: 999px;
        font-weight: 500;
        transition: transform 0.12s ease, opacity 0.12s ease, background-color 0.15s ease, border-color 0.15s ease;
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

    .btn-outline-light {
        --bs-btn-color: var(--accent);
        --bs-btn-border-color: var(--border);
        --bs-btn-hover-bg: rgba(255, 255, 255, 0.08);
        --bs-btn-hover-border-color: var(--border);
        --bs-btn-hover-color: var(--accent);
    }

    .btn-tinted {
        background-color: rgba(var(--brand-rgb), 0.15);
        color: var(--brand);
        border: 0;
    }

    .btn-tinted:hover,
    .btn-tinted:active {
        background-color: rgba(var(--brand-rgb), 0.25);
        color: var(--brand);
    }

    .page-hero-title {
        font-weight: 700;
        font-size: clamp(2rem, 5vw, 2.75rem);
        letter-spacing: -0.02em;
    }

    /* Height comes from the admin-configurable inline style (Setting
       profile_header_height) rather than a fixed clamp(), so the admin
       preview and the real page always match exactly. */
    .profile-header {
        position: relative;
        background-color: var(--bg-elevated);
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: flex-end;
        padding: 2rem 0 1.75rem;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.45) 0%, rgba(0, 0, 0, 0.8) 100%);
    }

    .profile-header .container {
        position: relative;
        z-index: 1;
    }

    .profile-handle {
        font-weight: 700;
        font-size: 1.4rem;
        letter-spacing: -0.01em;
    }

    .profile-display-name {
        color: var(--text-muted);
        font-weight: 500;
        margin-top: 0.15rem;
    }

    .profile-bio {
        color: var(--text-muted);
        margin-top: 0.6rem;
        max-width: 30rem;
        font-size: 0.95rem;
    }

    .profile-stats {
        display: flex;
        gap: 2rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .profile-stats .stat-num {
        font-weight: 700;
        font-size: 1.1rem;
        display: block;
    }

    .profile-stats .stat-label {
        color: var(--text-muted);
        font-size: 0.82rem;
    }

    .site-tabs {
        background-color: var(--bg-elevated);
        border-bottom: 1px solid var(--border);
    }

    .site-tabs-list {
        display: flex;
        gap: 2rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .site-tabs-list li a {
        display: inline-block;
        padding: 0.85rem 0;
        color: var(--text-muted);
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-bottom: 2px solid transparent;
        transition: color 0.15s ease, border-color 0.15s ease;
    }

    .site-tabs-list li a:hover {
        color: var(--accent);
    }

    .site-tabs-list li a.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }

    .album-card {
        position: relative;
        background-color: var(--bg-elevated);
        border-radius: 0.85rem;
        overflow: hidden;
        transition: transform 0.2s cubic-bezier(0.25, 1, 0.5, 1);
        height: 100%;
    }

    .album-card:active {
        transform: scale(0.97);
    }

    @media (hover: hover) {
        .album-card:hover {
            transform: translateY(-2px);
        }
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
        background-color: var(--bg-elevated-2);
    }

    .album-thumb-placeholder {
        aspect-ratio: 4 / 3;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-elevated-2);
        color: var(--text-tertiary);
    }

    .album-card-body {
        padding: 0.85rem 0.9rem 1rem;
    }

    .album-title {
        color: var(--accent);
        font-weight: 600;
        font-size: 0.95rem;
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
