<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
{{-- Anton + IBM Plex Mono (contact-sheet redesign) are loaded from
     home.blade.php itself, not here -- that design is home-page-only
     for now, and this partial is shared by every version-2 page. --}}

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

    /* Square, tight-gap grid -- used only by the Album page's sub-album
       list within version-2 (Home's overview grid uses .cs-tile instead).
       Restyled to the contact-sheet tokens; markup/structure unchanged. */
    .album-card {
        position: relative;
        background-color: #e2e4e0;
        border: 1px solid var(--cs-rule, rgba(21, 24, 26, 0.16));
        border-radius: 0;
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
            background: linear-gradient(to top, rgba(10, 11, 10, 0.88) 0%, rgba(10, 11, 10, 0.4) 60%, transparent 100%);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .album-card-body .album-title,
        .album-card-body .album-meta {
            color: #fff;
        }

        .album-card-body .album-meta {
            color: rgba(255, 255, 255, 0.72);
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
        background-color: #e2e4e0;
    }

    .album-thumb-placeholder {
        aspect-ratio: 1 / 1;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e2e4e0;
        color: var(--cs-ink-faint, rgba(21, 24, 26, 0.38));
    }

    .album-title {
        color: var(--cs-ink, #15181a);
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 0.15rem;
    }

    .album-meta {
        color: var(--cs-ink-soft, rgba(21, 24, 26, 0.62));
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

    /* ==========================================================
       Contact-sheet redesign -- Home page only.
       Namespaced with a `cs-` prefix so nothing here can collide
       with the rules above (shared by About/Contact/Album), and so
       this whole block is easy to find/remove as one unit.
       See docs/contact-sheet-implementation-plan.md.
       ========================================================== */
    :root {
        --cs-lightbox: #f2f4f1;
        --cs-ink: #15181a;
        --cs-ink-soft: rgba(21, 24, 26, 0.62);
        --cs-ink-faint: rgba(21, 24, 26, 0.38);
        --cs-grease: #a91f28;
        --cs-rule: rgba(21, 24, 26, 0.16);
    }

    body.cs-home {
        background: var(--cs-lightbox);
        color: var(--cs-ink);
        font-family: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace;
    }

    /* Sprocket rails -- fixed top/bottom chrome standing in for a film
       strip's perforations. Home-only for now (see the plan doc's
       "chrome scope" decision); every other version-2 page still uses
       partials/hero-subnav.blade.php, untouched. */
    .cs-rail {
        position: fixed;
        left: 0;
        right: 0;
        height: 22px;
        z-index: 50;
        background: var(--cs-ink);
        display: flex;
        align-items: center;
    }

    .cs-rail.top { top: 0; }
    .cs-rail.bottom { bottom: 0; }

    .cs-rail .cs-holes {
        flex: 1;
        align-self: stretch;
        background-image: repeating-radial-gradient(circle at 11px 11px, var(--cs-lightbox) 0 4px, transparent 4px 22px);
        background-size: 22px 22px;
    }

    .cs-rail .cs-counter {
        flex-shrink: 0;
        padding: 0 14px;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--cs-ink);
        color: var(--cs-lightbox);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.08em;
    }

    .cs-rail .cs-counter .n { color: #e8a4a8; }

    /* Identity strip -- this page's entire nav chrome (no traditional
       navbar in this design). */
    .cs-identity {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        /* The top rail is position:fixed (out of flow) -- without this,
           the identity strip renders at y:0 and sits hidden underneath
           it, since its own content is roughly the rail's height. */
        padding: 44px clamp(20px, 5vw, 56px) 0;
        margin-bottom: clamp(32px, 6vh, 64px);
    }

    .cs-identity .cs-mark {
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.14em;
        color: var(--cs-ink);
    }

    .cs-identity nav { display: flex; gap: 22px; }

    .cs-identity nav a {
        font-size: 11px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--cs-ink-soft);
        padding: 4px 0;
    }

    .cs-identity nav a:hover,
    .cs-identity nav a.active { color: var(--cs-grease); }

    /* No top padding here -- .cs-identity (which comes right before this
       in the DOM) already clears the fixed top rail. */

    /* Grease-pencil mark -- hand-drawn double-loop circle that draws
       itself on via stroke-dashoffset. Used on the hero feature (auto,
       shortly after load) and on grid tiles (on hover/focus). This SVG
       shape is intentionally not a perfect circle -- keep as-is. */
    .cs-grease-mark {
        position: absolute;
        inset: -9%;
        width: 118%;
        height: 118%;
        pointer-events: none;
        overflow: visible;
    }

    .cs-grease-mark ellipse {
        fill: none;
        stroke: var(--cs-grease);
        stroke-width: 2.4;
        stroke-linecap: round;
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        transition: stroke-dashoffset .6s cubic-bezier(.3, .7, .2, 1);
    }

    .cs-grease-mark ellipse:nth-child(2) {
        transition-delay: .1s;
        stroke-width: 2;
    }

    .cs-hero-feature.is-marked .cs-grease-mark ellipse,
    .cs-tile:hover .cs-grease-mark ellipse,
    .cs-tile:focus-visible .cs-grease-mark ellipse {
        stroke-dashoffset: 0;
    }

    /* Film grain -- applied only to individual photo elements (a
       wrapping position:relative element, never the <img> itself),
       never to the page background. */
    .cs-grain { position: relative; }

    .cs-grain::after {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.15;
        mix-blend-mode: overlay;
        pointer-events: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        background-size: 120px 120px;
    }

    /* Hero */
    .cs-hero {
        display: grid;
        grid-template-columns: 0.85fr 1.3fr;
        gap: clamp(24px, 5vw, 64px);
        align-items: center;
        padding: 0 clamp(20px, 5vw, 56px) clamp(48px, 8vh, 88px);
    }

    .cs-hero-text .eyebrow {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 11px;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--cs-ink-soft);
        margin-bottom: 14px;
    }

    .cs-hero-text h1 {
        font-family: 'Anton', 'Arial Narrow', sans-serif;
        text-transform: uppercase;
        font-size: clamp(2.6rem, 7vw, 5.4rem);
        line-height: .95;
        letter-spacing: .01em;
        margin: 0 0 20px;
    }

    .cs-hero-text p {
        color: var(--cs-ink-soft);
        max-width: 32rem;
        font-size: 14px;
        line-height: 1.7;
        margin: 0 0 28px;
    }

    .cs-hero-stats {
        display: flex;
        gap: 24px;
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--cs-ink-faint);
    }

    .cs-hero-feature { position: relative; }

    .cs-hero-feature-img {
        display: block;
        aspect-ratio: 4 / 3;
        width: 100%;
        overflow: hidden;
        background: #e2e4e0;
        position: relative;
        border: 1px solid var(--cs-rule);
    }

    .cs-hero-feature-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .cs-hero-feature-tag {
        position: absolute;
        left: -14px;
        bottom: -14px;
        background: var(--cs-lightbox);
        border: 1px solid var(--cs-ink);
        padding: 9px 16px;
        transform: rotate(-3deg);
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 600;
        pointer-events: none;
    }

    /* Services ticker -- infinite horizontal scroll, content duplicated
       once in the markup for a seamless loop (-50% is exactly one copy). */
    .cs-ticker {
        border-top: 1px solid var(--cs-rule);
        border-bottom: 1px solid var(--cs-rule);
        padding: 16px 0;
        overflow: hidden;
        margin-bottom: clamp(48px, 8vh, 88px);
    }

    .cs-ticker-track {
        display: flex;
        gap: 48px;
        white-space: nowrap;
        width: max-content;
        animation: cs-ticker-scroll 28s linear infinite;
    }

    .cs-ticker-track span {
        font-size: 13px;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--cs-ink-soft);
    }

    .cs-ticker-track span::after {
        content: '\2014';
        margin-left: 48px;
        color: var(--cs-ink-faint);
    }

    @keyframes cs-ticker-scroll {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }

    /* Portfolio grid -- one square tile per album (cropped covers are
       fine here; the "no crop" rule is specific to the Album page's
       own photo grid, not this overview). */
    .cs-sheet-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 12px;
        padding: 0 clamp(20px, 5vw, 56px);
        margin-bottom: 20px;
    }

    .cs-sheet-head h2 {
        font-family: 'Anton', 'Arial Narrow', sans-serif;
        text-transform: uppercase;
        font-size: clamp(1.6rem, 4vw, 2.4rem);
        margin: 0;
    }

    .cs-sheet-head .roll {
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--cs-ink-faint);
    }

    .cs-sheet {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(225px, 1fr));
        gap: 2px;
        padding: 0 clamp(20px, 5vw, 56px);
        margin-bottom: clamp(56px, 9vh, 100px);
    }

    .cs-tile {
        position: relative;
        aspect-ratio: 1;
        display: block;
        overflow: hidden;
        background: #e2e4e0;
    }

    .cs-tile img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .cs-tile-no {
        position: absolute;
        z-index: 2;
        top: 8px;
        left: 8px;
        font-size: 10px;
        letter-spacing: .06em;
        background: rgba(21, 24, 26, .72);
        color: var(--cs-lightbox);
        padding: 2px 6px;
    }

    .cs-tile-name {
        position: absolute;
        z-index: 2;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 28px 10px 10px;
        font-size: 11px;
        letter-spacing: .04em;
        color: #fff;
        background: linear-gradient(to top, rgba(10, 11, 10, .88) 0%, rgba(10, 11, 10, .5) 60%, transparent 100%);
        opacity: 0;
        transition: opacity .2s;
    }

    .cs-tile:hover .cs-tile-name,
    .cs-tile:focus-visible .cs-tile-name { opacity: 1; }

    .cs-tile-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--cs-ink-faint);
    }

    /* Process */
    .cs-process {
        padding: 0 clamp(20px, 5vw, 56px);
        margin-bottom: clamp(56px, 9vh, 100px);
        border-top: 1px solid var(--cs-rule);
    }

    .cs-process-step {
        display: flex;
        gap: 20px;
        padding: 22px 0;
        border-bottom: 1px solid var(--cs-rule);
    }

    .cs-process-step .n {
        font-family: 'Anton', 'Arial Narrow', sans-serif;
        font-size: 1.6rem;
        color: var(--cs-ink-faint);
        flex-shrink: 0;
        width: 2.4em;
    }

    .cs-process-step h3 {
        font-size: 13px;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin: 0 0 4px;
    }

    .cs-process-step p {
        font-size: 13px;
        color: var(--cs-ink-soft);
        margin: 0;
        max-width: 36rem;
    }

    /* CTA band */
    .cs-cta-band {
        background: var(--cs-ink);
        color: var(--cs-lightbox);
        text-align: center;
        padding: clamp(56px, 10vh, 110px) 20px;
        margin-bottom: clamp(48px, 8vh, 80px);
    }

    .cs-cta-band h2 {
        font-family: 'Anton', 'Arial Narrow', sans-serif;
        text-transform: uppercase;
        font-size: clamp(2rem, 6vw, 3.6rem);
        margin: 0 0 16px;
    }

    .cs-cta-band p {
        color: rgba(242, 244, 241, .66);
        font-size: 14px;
        margin: 0 0 32px;
    }

    .cs-cta-band a {
        display: inline-block;
        border: 1px solid var(--cs-lightbox);
        color: var(--cs-lightbox);
        padding: 14px 32px;
        font-size: 12px;
        letter-spacing: .1em;
        text-transform: uppercase;
        transition: background .15s, color .15s;
    }

    .cs-cta-band a:hover {
        background: var(--cs-lightbox);
        color: var(--cs-ink);
    }

    /* Contact block */
    .cs-contact {
        padding: 0 clamp(20px, 5vw, 56px) clamp(56px, 9vh, 96px);
        text-align: center;
    }

    .cs-contact h2 {
        font-family: 'Anton', 'Arial Narrow', sans-serif;
        text-transform: uppercase;
        font-size: clamp(1.4rem, 3.5vw, 2rem);
        margin: 0 0 12px;
    }

    .cs-contact p {
        color: var(--cs-ink-soft);
        font-size: 13px;
        margin: 0 0 20px;
    }

    .cs-contact-link {
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--cs-grease);
        border-bottom: 1px solid var(--cs-grease);
        padding-bottom: 2px;
    }

    .cs-empty {
        padding: 0 clamp(20px, 5vw, 56px) clamp(56px, 9vh, 96px);
        text-align: center;
        color: var(--cs-ink-soft);
        font-size: 13px;
    }

    /* Clearance for the fixed bottom rail -- scoped to this page only
       so no other version-2 page's footer spacing shifts. */
    body.cs-home footer {
        padding-bottom: 34px;
        color: var(--cs-ink-faint, rgba(21, 24, 26, .38));
        border-top-color: var(--cs-rule, rgba(21, 24, 26, .16));
    }

    @media (prefers-reduced-motion: reduce) {
        .cs-ticker-track { animation: none; }
        .cs-grease-mark ellipse { transition: none; }
    }

    @media (max-width: 767.98px) {
        .cs-hero {
            grid-template-columns: 1fr;
            padding: 0 20px 40px;
        }

        .cs-hero-feature-tag {
            left: 14px;
            bottom: -12px;
            font-size: 10px;
            padding: 8px 14px;
        }

        .cs-identity { padding: 0 20px; }

        .cs-ticker-track { gap: 32px; animation-duration: 18s; }
        .cs-ticker-track span::after { margin-left: 32px; }

        .cs-sheet {
            grid-template-columns: repeat(2, 1fr);
            padding: 0 20px;
        }

        .cs-sheet-head,
        .cs-process,
        .cs-contact,
        .cs-empty { padding-left: 20px; padding-right: 20px; }

        .cs-process-step .n { width: 1.8em; font-size: 1.2rem; }
    }
</style>
