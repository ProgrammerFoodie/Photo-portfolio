<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        /* Version 2.0: Instagram-influenced layout, but warm and dark
           rather than IG's stark white/blue -- a personal, lived-in feel.
           Warm charcoal base (not black), same brand coral for continuity
           with the rest of the site's identity. */
        --bg: #1e1815;
        --bg-elevated: #2a2119;
        --bg-elevated-2: #362a20;
        --border: rgba(255, 238, 222, 0.12);
        --text-muted: rgba(255, 238, 222, 0.68);
        --text-tertiary: rgba(255, 238, 222, 0.4);
        --accent: #f7f1ea;
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
        background-color: rgba(30, 24, 21, 0.72);
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

    /* About/Contact page header (version-2) -- same eyebrow + bold title
       typography as the homepage hero, without the full-bleed photo grid.
       Falls back to the flat bg-elevated color when there's no cover image.
       Height comes from the admin-configurable inline style (Setting
       profile_header_height); flex + bottom alignment keeps the title
       sitting at the same spot regardless of that height. */
    .page-hero {
        position: relative;
        background-color: var(--bg-elevated);
        background-size: cover;
        background-position: center;
        border-bottom: 1px solid var(--border);
        padding: 0 0 2rem;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
    }

    /* The faint border reads as a bright seam against a photo background,
       so drop it there -- the gradient overlay already separates the
       header from the subnav below it. */
    .page-hero.has-cover {
        border-bottom: 0;
    }

    .page-hero.has-cover::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(20, 14, 10, 0.55) 0%, rgba(20, 14, 10, 0.85) 100%);
    }

    .page-hero .container {
        position: relative;
        z-index: 1;
    }

    .page-hero h1 {
        font-weight: 800;
        font-size: clamp(2rem, 5vw, 3rem);
        line-height: 1.05;
        letter-spacing: -0.02em;
        color: var(--accent);
        margin: 0;
    }

    /* Non-home pages (about/contact/album) keep the original full-bleed
       cover header markup -- restyled here to match, no template changes
       needed there. */
    /* Height comes from the admin-configurable inline style (Setting
       profile_header_height) rather than a fixed clamp(). */
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
        background: linear-gradient(180deg, rgba(20, 14, 10, 0.45) 0%, rgba(20, 14, 10, 0.85) 100%);
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

    /* Profile header (home page only) -- avatar beside handle/bio/stats
       instead of a full-bleed cover photo. Deliberately understated: no
       gradient "story ring", soft shadow instead of a heavy border, more
       generous whitespace -- reads as a modern personal site rather than
       a literal platform clone. */
    .ig-profile {
        padding: 3.5rem 0 2rem;
        border-bottom: 1px solid var(--border);
    }

    .ig-profile-row {
        display: flex;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .ig-avatar {
        width: 104px;
        height: 104px;
        border-radius: 50%;
        object-fit: cover;
        background-color: var(--bg-elevated-2);
        border: 1px solid var(--border);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        flex-shrink: 0;
    }

    .ig-avatar-placeholder {
        width: 104px;
        height: 104px;
        border-radius: 50%;
        background-color: var(--bg-elevated-2);
        border: 1px solid var(--border);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-tertiary);
    }

    .ig-profile-info {
        min-width: 0;
    }

    .ig-profile-info .profile-handle {
        font-size: 1.6rem;
        letter-spacing: -0.02em;
    }

    .ig-profile-info .profile-stats {
        margin-top: 1.25rem;
        gap: 2.5rem;
    }

    .ig-profile-info .profile-stats .stat-num {
        font-size: 1.2rem;
    }

    .ig-profile-info .profile-stats .stat-label {
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.72rem;
    }

    /* Quick-access row of albums: rounded-square tiles (not literal story
       circles), a hairline border instead of a colored ring -- a subtler,
       more modern take on the same "jump to an album" idea. */
    .ig-highlights {
        display: flex;
        gap: 1.25rem;
        overflow-x: auto;
        padding: 1.5rem 0;
        border-bottom: 1px solid var(--border);
        scrollbar-width: thin;
    }

    .ig-highlight {
        flex-shrink: 0;
        width: 84px;
        text-align: center;
        color: var(--text-muted);
    }

    .ig-highlight-bubble {
        width: 76px;
        height: 76px;
        border-radius: 1rem;
        border: 1px solid var(--border);
        background-color: var(--bg-elevated-2);
        margin: 0 auto 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        transition: border-color 0.15s ease;
    }

    .ig-highlight:hover .ig-highlight-bubble {
        border-color: rgba(var(--brand-rgb), 0.5);
    }

    .ig-highlight-bubble img,
    .ig-highlight-bubble .placeholder {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .ig-highlight-bubble .placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-tertiary);
    }

    .ig-highlight-name {
        font-size: 0.72rem;
        letter-spacing: 0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
        border-bottom-color: var(--brand);
    }

    /* The only nav bar on version-2 pages (home/about/contact) -- subtle
       background, icons, roomier than the plain text site-tabs used on
       the default theme. Sticky so it's still reachable once you've
       scrolled past the hero/header into the page content. */
    .hero-subnav {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: var(--bg-elevated);
        border-bottom: 1px solid var(--border);
    }

    .hero-subnav-list {
        display: flex;
        gap: 2.5rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .hero-subnav-list li a {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 1.1rem 0;
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: color 0.15s ease, border-color 0.15s ease;
    }

    .hero-subnav-list li a i {
        font-size: 1.1rem;
        color: var(--text-tertiary);
        transition: color 0.15s ease;
    }

    .hero-subnav-list li a:hover,
    .hero-subnav-list li a:hover i {
        color: var(--accent);
    }

    .hero-subnav-list li a.active {
        color: var(--accent);
        border-bottom-color: var(--brand);
    }

    .hero-subnav-list li a.active i {
        color: var(--brand);
    }

    /* Deliberately understated -- sits in the same bar as Albums/About/
       Contact but shouldn't compete with them for attention. */
    .hero-subnav-login {
        display: inline-flex;
        align-items: center;
        color: var(--text-muted);
        font-size: 1.2rem;
        transition: color 0.15s ease;
    }

    .hero-subnav-login:hover {
        color: var(--accent);
    }

    /* Square, tight-gap Instagram-style grid. Same .album-card/.album-thumb
       markup as the default theme -- only the visuals change here. */
    .album-card {
        position: relative;
        background-color: var(--bg-elevated);
        border-radius: 0.75rem;
        overflow: hidden;
        transition: transform 0.15s ease;
        height: 100%;
    }

    .album-card:active {
        transform: scale(0.97);
    }

    .album-card-body {
        padding: 0.6rem 0.7rem 0.7rem;
    }

    @media (min-width: 992px) {
        .album-card-body {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 0.75rem;
            background: linear-gradient(to top, rgba(10, 6, 4, 0.9), rgba(10, 6, 4, 0.3) 55%, transparent);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .album-card:hover .album-card-body {
            opacity: 1;
        }
    }

    .album-thumb {
        aspect-ratio: 1 / 1;
        width: 100%;
        object-fit: cover;
        display: block;
        background-color: var(--bg-elevated-2);
    }

    .album-thumb-placeholder {
        aspect-ratio: 1 / 1;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-elevated-2);
        color: var(--text-tertiary);
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

    /* Full-bleed homepage hero: a fixed, deliberately composed photo
       mosaic (not an auto-packed tile grid) with one cell replaced by
       the site's headline. Every cell of the 4x3 grid is explicitly
       assigned to a specific photo/headline below, so it always fills
       exactly 100vh with zero gaps -- regardless of viewport size --
       instead of depending on how many photos happen to be available.
       Kept sparse (7 photos) on purpose -- fewer, bigger tiles read as
       a deliberate composition rather than a busy tiled wall. */
    .hero-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(3, 1fr);
        gap: 4px;
        height: 100vh;
        overflow: hidden;
        position: relative;
        background-color: var(--bg);
    }

    .hero-tile {
        display: block;
        position: relative;
        overflow: hidden;
    }

    .hero-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }

    .hero-photo:hover img {
        transform: scale(1.05);
    }

    .hero-headline {
        background-color: var(--bg-elevated);
        border: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: clamp(1.5rem, 4vw, 3rem);
    }

    /* Explicit cell assignments only apply at desktop widths -- mobile
       uses a simple auto-flowing 2-column grid instead (see below). */
    @media (min-width: 768px) {
        .hero-headline { grid-column: 2 / 4; grid-row: 1 / 3; }

        .hero-photo:nth-of-type(1) { grid-column: 1; grid-row: 1 / 3; }
        .hero-photo:nth-of-type(2) { grid-column: 4; grid-row: 1; }
        .hero-photo:nth-of-type(3) { grid-column: 4; grid-row: 2; }
        .hero-photo:nth-of-type(4) { grid-column: 1; grid-row: 3; }
        .hero-photo:nth-of-type(5) { grid-column: 2; grid-row: 3; }
        .hero-photo:nth-of-type(6) { grid-column: 3; grid-row: 3; }
        .hero-photo:nth-of-type(7) { grid-column: 4; grid-row: 3; }
    }

    .hero-eyebrow {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--brand);
        text-transform: uppercase;
        letter-spacing: 0.14em;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .hero-headline h1 {
        font-weight: 800;
        font-size: clamp(2rem, 5vw, 3.5rem);
        line-height: 1.05;
        letter-spacing: -0.02em;
        color: var(--accent);
        margin: 0 0 1rem;
    }

    .hero-tagline {
        color: var(--text-muted);
        max-width: 26rem;
        margin-bottom: 1.5rem;
    }

    .hero-meta {
        display: flex;
        gap: 0.75rem;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.78rem;
    }

    .hero-scroll-cue {
        position: absolute;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.3rem;
        color: var(--accent);
        text-shadow: 0 1px 6px rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 0.7rem;
        z-index: 2;
        animation: hero-scroll-bounce 2s infinite;
        pointer-events: none;
    }

    @keyframes hero-scroll-bounce {
        0%, 100% { transform: translate(-50%, 0); }
        50% { transform: translate(-50%, 6px); }
    }

    @media (max-width: 767.98px) {
        .hero-grid {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: none;
            grid-auto-rows: 18vh;
            height: 100vh;
        }

        /* Undo the desktop's fixed 6x4 cell assignments -- mobile uses
           a simple auto-flowing 2-column grid instead. */
        .hero-photo {
            grid-column: auto;
            grid-row: auto;
        }

        .hero-headline {
            grid-column: span 2;
            grid-row: span 2;
            padding: 1.5rem;
        }

        /* Mobile shows one fewer photo than desktop (6 vs 7) -- feels
           less cramped stacked in 2 columns. */
        .hero-photo:nth-of-type(7) {
            display: none;
        }
    }

</style>
