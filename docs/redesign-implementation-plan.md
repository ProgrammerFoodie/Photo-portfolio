# Public-Site Redesign — Implementation Plan

**Status:** Draft, not yet approved. Ugis is reviewing this before any code is written.
**Audience:** Whoever (human or LLM) implements this — assume no memory of the design conversation that produced it.
**Design reference:** A visual mockup exists as a published Claude Artifact ("Ugis Photography Redesign") showing 8 static artboards (desktop + mobile versions of Home, Album, About, Contact). This document is the authoritative spec — where the mockup and this document disagree, or where the mockup is ambiguous, this document wins, because it accounts for real data, edge cases, and existing functionality the static mockup couldn't show.

---

## 0. How to use this document

Read the whole thing before touching any file. Sections are ordered so that later sections depend on earlier ones (design tokens before components, components before pages, pages before backend changes). Do not skip Section 1 — it contains hard constraints that override anything that looks convenient later in the document.

Every code block in this plan is meant to be adapted, not copy-pasted blindly where it references specific numbers (e.g. array indices, breakpoint values) — but *is* meant to be copied close to verbatim where it's markup/CSS structure and SVG icon paths, because re-deriving those from a text description is exactly the kind of "assumed to be obvious" step that causes errors.

---

## 1. Non-negotiable ground rules

These come from the project's own operating rules (`CLAUDE.md`), restated here so this document is self-contained:

1. **Scope is the `version-2` theme only.** The site has two themes controlled by `Setting::get('theme')` — `'default'` and `'version-2'` — selected via `App\Support\Theme::is('version-2')` checks scattered through the Blade views. **Every change in this plan lives inside the `version-2` branch of a conditional, or in a file that is only ever included when `version-2` is active.** Never touch the `default`-theme partials (`partials/site-nav.blade.php`, `partials/site-tabs.blade.php`, `partials/site-styles.blade.php`) or the `@else` branches of the conditionals in `home.blade.php`, `pages/about.blade.php`, `pages/contact.blade.php`, `albums/show.blade.php`. Before starting, confirm the theme is actually set to `version-2` in the database (check the admin Settings page, or `Setting::get('theme')` in tinker) — don't assume.
2. **Do not touch the admin dashboard.** Nothing under `resources/views/admin/**`, nothing in `routes/web.php`'s `admin` prefix group, no admin controllers. This redesign is public-facing pages only: Home, Album/Gallery, About, Contact.
3. **No database migrations, no schema changes.** Everything this plan needs already exists in the schema (`Album.sort_order`, `Album.parent_id`, `Photo.album_id`, etc. — verified against the actual models, see Section 7). If you find yourself wanting a new column, stop and re-read Section 7 — the ordering data you need is already there.
4. **No new JavaScript framework or build-tool changes.** The `version-2` theme currently loads Bootstrap 5 (CSS + bundled JS) and Bootstrap Icons from a CDN, plus Google Fonts, all via a single `<style>`/`<link>` block in `partials/site-styles-version-2.blade.php`. It does **not** use the project's Tailwind/Vite pipeline for this theme (Tailwind is wired up in `tailwind.config.js` but the `version-2` partial hand-rolls its own CSS with CSS custom properties instead — this redesign continues that existing pattern, not Tailwind classes). Do not introduce Alpine.js, a bundler step, or npm packages for this work — plain `<script>` tags and vanilla JS, matching how `albums/show.blade.php` already does it.
5. **Do not touch `albums/show.blade.php`'s existing JavaScript logic.** That file contains a hand-built justified-row photo grid layout engine, infinite-scroll batching, a lightbox with keyboard/swipe navigation and preloading, and a checkbox-based multi-select-and-zip-download flow. It is substantial, already works, and is **not** part of what changed in the mockup's visual direction. Section 6.4 explains exactly what to change (CSS only, plus one small new toolbar element) and what must not move.
6. **Mobile/responsive layout is this project's known fragile area.** Treat every measurement in this plan as something to actually verify in a real browser at real widths (375px, 390px, 414px, 768px, 1280px+), not just trust because a static mockup artboard looked right at one fixed width.
7. **This document itself must not be deployed anywhere.** It's a planning artifact for the repo (`docs/`), not something that goes near the live server.

---

## 2. What you're building — summary

A visual-only redesign of four public page templates, replacing the current "Instagram-influenced dark warm charcoal" look with a new **dark, warm-espresso, editorial-minimal** aesthetic. All four pages keep every piece of functionality they currently have — this is a re-skin plus two small, deliberate additions (a prev/next album control, described in Section 6.4 and Section 7). No content, routes, or data shapes change except the two additions called out explicitly.

The four templates:

| Page | Blade view | Current version-2 hero pattern | New pattern |
|---|---|---|---|
| Home | `resources/views/home.blade.php` | Full-bleed 4×3 photo mosaic + centered nav below it | Asymmetric two-column hero (headline + one overlapping featured photo) + an asymmetric "collage" grid of album covers |
| Album / Gallery | `resources/views/albums/show.blade.php` | Plain `.album-header` block + `site-tabs` or `hero-subnav` | Same nav bar as other pages + a new floating pill-shaped "subbar" (prev/next album, album title/count, download shortcut) sitting above the **existing, unchanged** justified-photo-grid |
| About | `resources/views/pages/about.blade.php` | `.page-hero` block + `hero-subnav` | New nav bar + centered eyebrow/large-title header + editorial body copy with a drop-cap on the first paragraph |
| Contact | `resources/views/pages/contact.blade.php` | `.page-hero` block + Bootstrap card form | New nav bar + centered header + underline-style form fields (no boxes) + icon-only social links row |

---

## 3. File inventory

### Files you will fully rewrite (their `version-2`-relevant content, not the whole file for the ones with an `@else` branch)

| File | What changes |
|---|---|
| `resources/views/partials/site-styles-version-2.blade.php` | **Entire file is replaced.** This is the single CSS source of truth for the whole theme — see Section 4 and 6 for its full new contents. |
| `resources/views/partials/hero-subnav.blade.php` | **Entire file is replaced.** Becomes the new shared nav bar component (desktop nav-links + wordmark + icons, mobile hamburger + drawer). Used by all four pages, same as today. |

### Files you edit (only the `@if (\App\Support\Theme::is('version-2'))` branch — leave the `@else` branch completely untouched)

| File | What changes |
|---|---|
| `resources/views/home.blade.php` | Replace the `<section class="hero-grid">...</section>` block with the new hero markup (Section 6.2), replace the album grid markup with the new collage grid (Section 6.2). |
| `resources/views/pages/about.blade.php` | Replace the `<header class="page-hero">...</header>` block with the new header markup (Section 6.3). Wrap the existing body-text paragraph so it gets the drop-cap treatment. |
| `resources/views/pages/contact.blade.php` | Replace the `<header class="page-hero">...</header>` block with the new header markup. Replace the Bootstrap-card form markup and the social-links markup with the new underline-form and icon-row markup (Section 6.5). Keep every `@php` block, the honeypot field, CSRF token, validation/status blocks — see Section 6.5 for exactly what must be preserved. |
| `resources/views/albums/show.blade.php` | Replace the `<header class="album-header">...</header>` + the `@include('partials.hero-subnav', ...)` line with: new nav bar include + new subbar markup (Section 6.4). Replace the `<style>` block's *visual* rules (colors, radii) to match new tokens — **do not** touch the JS `<script>` block at the bottom, and do not touch the layout-algorithm CSS rules that are structural rather than cosmetic (see Section 6.4 for the exact list of which rules are cosmetic vs. structural). |

### Files you edit for the two functional additions

| File | What changes |
|---|---|
| `app/Http/Controllers/GalleryController.php` | `show()` method gains previous/next top-level-album lookup, passed to the view. Exact code in Section 7.1. |
| `app/Http/Controllers/HomeController.php` | `heroPhotos` query's `limit(7)` becomes `limit(1)` (the new hero only needs one featured photo, not seven), and it should eager-load the photo's `album` relationship. Exact code in Section 7.2. |

Nothing else changes. No new files, no new routes, no migrations.

---

## 4. Design tokens & global assets

Every one of these lives in `partials/site-styles-version-2.blade.php`, inside a `:root { ... }` block, exactly like the file does today (it already has a `:root` with a comment explaining the previous "Instagram-influenced" palette — replace that whole block and its comment).

### 4.1 Color tokens

```css
:root {
    /* Redesign, [DATE YOU IMPLEMENT THIS]: warm espresso base, editorial-
       minimal direction. Deliberately monochrome except one terracotta
       accent — no per-platform social brand colors, no bright primary
       button color. Replaces the previous warm-charcoal/coral palette. */
    --bg: #1b140f;
    --bg-elevated: #271e17;
    --bg-elevated-2: #332619;
    --hairline: rgba(247, 238, 225, 0.12);
    --text: #f7ede0;
    --text-muted: rgba(247, 238, 225, 0.66);
    --text-tertiary: rgba(247, 238, 225, 0.55);
    --accent: #c8783f;
    --accent-soft: rgba(200, 120, 63, 0.18);

    /* Not present in the mockup — needed for real form states the mockup
       didn't show. Pick a warm red-orange that reads as "error" without
       clashing with the terracotta accent. */
    --danger: #d9564a;
    --danger-soft: rgba(217, 86, 74, 0.16);
}
```

**Why these exact values, so you don't second-guess them:** `--text-tertiary` at 0.55 alpha on `--bg` computes to ~5.5:1 WCAG contrast, `--text-muted` at 0.66 to ~7.4:1, and `--accent` as a text color on `--bg` to ~5.4:1 — all already verified against WCAG AA (4.5:1 minimum for normal-size text) during the design pass. **Do not lighten `--bg` or darken these text tokens without re-checking contrast** — it's easy to accidentally regress this when "just tweaking" a color.

### 4.2 Typography

Two Google Fonts, loaded once at the top of `site-styles-version-2.blade.php` (replacing the current Bootstrap+Inter link block):

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
```

Keep the Bootstrap CSS/Icons links — Bootstrap's JS bundle (modal, etc.) is still used by `albums/show.blade.php`'s lightbox, and Bootstrap Icons are still used in a couple of places you're not touching. You're only replacing the *font* link (Inter-only → Inter+Cormorant Garamond) and the color/component CSS below it.

- **Body/UI font:** `'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif` — same stack the current theme already uses, just keep it.
- **Display/serif font** (wordmark, page headlines, italic lede lines, drop-cap): `'Cormorant Garamond', Georgia, serif`.
- Never use a third font. Never substitute a different Google Font "because it looks similar" — the exact weight/italic axes requested in the `<link>` above (`500`, `600`, `700`, italic `500`) are the ones actually used; if you need a weight not listed there, add it to the URL rather than guessing the font supports it.

### 4.3 Spacing, radius, and reusable CSS snippets

These three CSS snippets are used on **every one of the four pages** — define them once in `site-styles-version-2.blade.php` and reuse the classes everywhere, don't redefine them per page.

**Grain texture overlay** (subtle noise, applied to every page's background for tactile warmth — this is a deliberate custom touch, not decoration you can drop):

```css
.page { position: relative; }
.grain {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    opacity: 0.05;
    mix-blend-mode: overlay;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    background-size: 140px 140px;
}
.content { position: relative; z-index: 1; }
```

Every page's `<body>` gets wrapped as:

```html
<body>
<div class="page">
    <div class="grain"></div>
    <div class="content">
        <!-- everything that's currently a direct child of <body> goes here -->
    </div>
</div>
</body>
```

`pointer-events: none` on `.grain` means it can never block clicks — that's why it's safe to leave it there even though it's visually on top of the background. `.content` has `position: relative; z-index: 1` so real page content always paints above the grain layer regardless of DOM order.

**Ornament divider** (small 3-dot mark, replaces plain `border-top: 1px solid var(--hairline)` dividers — used directly above every page's `<footer>`):

```css
.ornament { display: flex; align-items: center; justify-content: center; gap: 7px; padding: 0 0 32px; }
.ornament span { width: 4px; height: 4px; border-radius: 50%; background: var(--accent); }
.ornament span:nth-child(2) { width: 5px; height: 5px; }
```

```html
<div class="ornament"><span></span><span></span><span></span></div>
```

On mobile artboards this same markup is used but with `padding: 0 0 28px` instead of `32px` — a small deliberate reduction, not a typo, keep it.

**Wordmark + logomark** (used in the nav bar on every page — see Section 6.1 for the full nav bar markup, this is just the icon+wordmark unit on its own):

```css
.wordmark-group { display: flex; align-items: center; gap: 9px; }
.wordmark-mark { width: 19px; height: 19px; color: var(--accent); flex-shrink: 0; }
.wordmark { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; font-size: 16px; letter-spacing: 0.16em; text-transform: uppercase; }
```

```html
<div class="wordmark-group">
    <svg class="wordmark-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <path d="M12 3 19 7.5 19 16.5 12 21 5 16.5 5 7.5Z"/>
        <circle cx="12" cy="12" r="3.4" stroke-width="1.2"/>
    </svg>
    <div class="wordmark">{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}</div>
</div>
```

On mobile artboards `.wordmark-mark` is `17px × 17px` and `.wordmark` is `font-size: 15px` — slightly smaller, matching the smaller topbar. **This is the only logomark in the design.** It is an original geometric shape (hexagon outline + small centered circle, evoking a camera aperture) — it is not a recreation of any camera brand's actual logo, and it must stay that way; don't "improve" it into something that looks like a real lens/aperture icon from a stock icon set, since that risks looking like a trademark.

**Note on wordmark text:** the mockup hardcoded `"Ugis"`. The real implementation must use live data — `Setting::get('profile_handle')`, falling back to `Setting::get('site_title')` if the handle is empty, matching the fallback pattern `home.blade.php` already uses elsewhere (`{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}`).

### 4.4 Icon set

Every icon in this design is a hand-drawn inline SVG (`viewBox="0 0 24 24"`, `stroke="currentColor"`, no fills except where noted) — **never** use Bootstrap Icons' `<i class="bi bi-...">` classes for anything in this redesign, even though the Bootstrap Icons stylesheet stays loaded for the parts of the site you're not touching. Mixing the two icon systems within the redesigned pages would look inconsistent. Here is the complete set, to copy verbatim:

```html
<!-- Hamburger menu (mobile nav open) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>

<!-- Close / X (mobile nav close) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>

<!-- Instagram (generic camera glyph — deliberately NOT a recreation of the actual Instagram logo, see Section 6.5 for why) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h6L16 8h4a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V9a1 1 0 011-1z"/><circle cx="12" cy="13" r="3.5"/></svg>

<!-- Email / mail -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/></svg>

<!-- Login (arrow into bracket) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3"/><path d="M10 8l4 4-4 4"/><path d="M14 12H3"/></svg>

<!-- Generic link / website (social-link fallback icon, see Section 6.5) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 6 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-6-3.8-9s1.3-6.5 3.8-9z"/></svg>

<!-- Chevron left (album subbar: previous) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>

<!-- Chevron right (album subbar: next) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>

<!-- Download (album subbar shortcut, and reused as-is for the send-message arrow's "download" visual family) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="M7.5 11.5L12 16l4.5-4.5"/><path d="M5 19h14"/></svg>

<!-- Camera (empty-cover placeholder icon, used wherever an album/photo has no image yet) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h6L16 8h4a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V9a1 1 0 011-1z"/><circle cx="12" cy="13" r="3.5"/></svg>

<!-- Send / arrow-right (contact form submit button) -->
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
```

Note the **Instagram icon and the Camera placeholder icon are the same SVG.** That's intentional (both represent "a photo"), not a mistake to fix.

---

## 5. Responsive strategy

The mockup showed two fixed widths per page (roughly 1280px desktop, 390px mobile) as separate static artboards. Real code needs one fluid stylesheet with an actual breakpoint. Decision, and it's final for this pass — **use a single breakpoint at `768px`** (`max-width: 767.98px` for "mobile", everything at `768px` and above is "desktop"), matching Bootstrap 5's own `md` breakpoint so it stays consistent with the rest of the site's existing responsive conventions.

At `< 768px`, every page switches:

- Nav bar: horizontal `Portfolio / About / Contact` text links **disappear**, replaced by a hamburger icon (left) that opens a full-screen drawer (see Section 6.1 — this drawer did not exist in the mockup and is specified fresh here, don't skip it).
- Home hero: two-column grid becomes a single stacked column (headline first, then the featured image).
- Home collage grid: 4 columns → 2 columns, and the `grid-auto-rows` value shrinks (see Section 6.2 exact values).
- Album subbar: shrinks (smaller buttons, tighter title column) but keeps the same 44px-minimum tap targets — see Section 6.4.
- Contact: two-column (form + sidebar) becomes single column, form-first, socials below.
- All horizontal paddings around content shrink from `40px`/`32px`/`24px` to `18px`–`26px` (exact values are per-component, given in each component's CSS below — don't invent your own numbers here, use the ones specified).

**Optional tablet polish (not required for this pass):** the desktop collage grid's 4 columns may feel cramped between 768–1024px. If, when you actually test it in a browser, it looks cramped, you may add a second media query dropping to 3 columns in that range. Do not do this preemptively without checking — it's explicitly optional.

**On not using `clamp()` for headline sizing:** the mockup's large headline (`Photographs, mostly of light.`) uses a fixed `52px` desktop / `34px` mobile size, switched at the breakpoint, rather than a fluid `clamp()` value. This is a deliberate choice for predictability given this project's history of mobile layout bugs — two fixed, tested sizes are easier to reason about than one fluid formula that has to be checked across the whole range. Keep this pattern (fixed sizes per breakpoint) for every other large text element in this redesign too, rather than introducing `clamp()`.

---

## 6. Component specifications

### 6.1 Nav bar (`partials/hero-subnav.blade.php`) — used by all four pages

This file is included identically today by `home.blade.php`, `pages/about.blade.php`, `pages/contact.blade.php`, and `albums/show.blade.php`, always as `@include('partials.hero-subnav', ['showLogin' => true])`. Keep that exact same include signature — don't change how it's called from the four page files, only what's inside the partial.

The current file renders a single `<nav>` with icon+text links (Albums/About/Contact) and a login icon, using Bootstrap Icons and `request()->routeIs(...)` to mark the active link. **Preserve that active-link logic exactly** — it's the mechanism that makes the current nav item look "on" when you're on that page, and the redesign needs the same mechanism, just restyled.

Replace the whole file with:

```blade
@php
    $showLogin = $showLogin ?? false;
    $navItems = [
        ['route' => 'home', 'matches' => ['home', 'albums.show'], 'label' => 'Portfolio'],
        ['route' => 'about', 'matches' => ['about'], 'label' => 'About'],
        ['route' => 'contact', 'matches' => ['contact'], 'label' => 'Contact'],
    ];
@endphp
<nav class="navbar" x-data="{ mobileOpen: false }" x-cloak style="display:none">
    {{-- x-data/x-cloak above are a stray leftover of an Alpine draft — DO NOT actually use Alpine here, see the vanilla-JS version below. This comment block is intentionally left as a warning: do not add Alpine to this file. --}}
</nav>
```

**Stop. That snippet above is wrong on purpose — it's here to make a point:** ground rule #4 says no Alpine for this work. The actual nav bar markup, with the mobile drawer implemented in plain vanilla JS matching `albums/show.blade.php`'s existing style, is this:

```blade
@php
    $showLogin = $showLogin ?? false;
    $navItems = [
        ['route' => 'home', 'matches' => ['home', 'albums.show'], 'label' => 'Portfolio'],
        ['route' => 'about', 'matches' => ['about'], 'label' => 'About'],
        ['route' => 'contact', 'matches' => ['contact'], 'label' => 'Contact'],
    ];
    $socialLinks = \App\Models\Setting::socialLinks();
@endphp

<nav class="navbar">
    <button type="button" class="icon-btn nav-menu-toggle" id="navMenuOpen" aria-label="Open menu" aria-expanded="false" aria-controls="navDrawer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>

    <div class="nav-links">
        @foreach ($navItems as $item)
            <a class="nav-link {{ request()->routeIs(...$item['matches']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
        @endforeach
    </div>

    <div class="wordmark-group">
        <svg class="wordmark-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M12 3 19 7.5 19 16.5 12 21 5 16.5 5 7.5Z"/><circle cx="12" cy="12" r="3.4" stroke-width="1.2"/></svg>
        <div class="wordmark">{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}</div>
    </div>

    <div class="nav-icons">
        @foreach ($socialLinks as $link)
            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $link['label'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 6 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-6-3.8-9s1.3-6.5 3.8-9z"/></svg>
            </a>
        @endforeach
        @if ($showLogin)
            <a href="{{ route('login') }}" aria-label="Login">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3"/><path d="M10 8l4 4-4 4"/><path d="M14 12H3"/></svg>
            </a>
        @endif
    </div>
</nav>

<div class="nav-drawer" id="navDrawer" hidden>
    <div class="nav-drawer-backdrop" id="navDrawerBackdrop"></div>
    <div class="nav-drawer-panel">
        <button type="button" class="icon-btn nav-drawer-close" id="navDrawerClose" aria-label="Close menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <div class="nav-drawer-links">
            @foreach ($navItems as $item)
                <a class="{{ request()->routeIs(...$item['matches']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
            @endforeach
        </div>
        @if ($showLogin)
            <a class="nav-drawer-login" href="{{ route('login') }}">Login</a>
        @endif
    </div>
</div>

<script>
(function () {
    var openBtn = document.getElementById('navMenuOpen');
    var closeBtn = document.getElementById('navDrawerClose');
    var backdrop = document.getElementById('navDrawerBackdrop');
    var drawer = document.getElementById('navDrawer');
    if (!openBtn || !drawer) { return; }

    function openDrawer() {
        drawer.hidden = false;
        document.body.style.overflow = 'hidden';
        openBtn.setAttribute('aria-expanded', 'true');
    }
    function closeDrawer() {
        drawer.hidden = true;
        document.body.style.overflow = '';
        openBtn.setAttribute('aria-expanded', 'false');
    }

    openBtn.addEventListener('click', openDrawer);
    closeBtn.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !drawer.hidden) { closeDrawer(); }
    });
})();
</script>
```

**Important details, explained so nothing here is "obvious but wrong":**

- `route('home')` is used for the "Portfolio" link (not a literal `/portfolio` URL) — the site's actual route name for the album-listing home page is `home`, confirmed in `routes/web.php`. The **label** says "Portfolio", the **route** is `home`. Don't create a new route.
- The active-state check `request()->routeIs('home', 'albums.show')` for the Portfolio link matches the *current* file's behavior exactly (being on an individual album page also highlights "Portfolio" as active, since albums are conceptually part of the portfolio listing) — preserve this, it's not a bug.
- `$socialLinks` in the nav icon row uses the **same** `Setting::socialLinks()` data source as the Contact page's social row (Section 6.5). The nav bar version only shows the icons (no labels, no platform-specific icon) — every social link in the nav bar uses the identical generic "link" icon, regardless of platform, exactly like the fallback described in Section 6.5. This is consistent, not an oversight.
- The mobile drawer is **new functionality that did not exist before this redesign** (the old nav never needed a drawer because it was never hidden). It must be genuinely accessible: focus doesn't get trapped in this v1 implementation (acceptable for a first pass — flag it as a known limitation, don't silently skip building the drawer at all), Escape key closes it, clicking the backdrop closes it, and `aria-expanded`/`aria-controls`/`hidden` are wired up as shown above. If this is code-reviewed later and focus-trapping is required, that's a follow-up, not a blocker for this pass.
- `.nav-menu-toggle` and the whole `.nav-links` row are togged by CSS media query (`.nav-menu-toggle` hidden at `≥768px`, `.nav-links` hidden at `<768px`) — see the CSS below. The drawer markup itself is always in the DOM (`hidden` attribute controls visibility), it's not conditionally rendered per breakpoint in Blade — one markup, CSS + the JS `hidden` toggle handle both states.

CSS for this component (add to `site-styles-version-2.blade.php`):

```css
.navbar { display: flex; align-items: center; justify-content: space-between; padding: 32px 40px 0; }
.nav-links { display: flex; gap: 30px; }
.nav-link { font-size: 11px; font-weight: 500; letter-spacing: 0.14em; text-transform: uppercase; color: var(--text-muted); padding: 6px 0; }
.nav-link:hover, .nav-link.active { color: var(--accent); }
.nav-icons { display: flex; align-items: center; gap: 6px; }
.nav-icons a { color: var(--text-muted); display: flex; align-items: center; justify-content: center; padding: 14px; margin: -14px; }
.nav-icons a:hover { color: var(--accent); }
.nav-icons svg { width: 17px; height: 17px; }

.icon-btn { color: var(--text-muted); display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; margin: -11px; }
.icon-btn svg { width: 19px; height: 19px; }
.nav-menu-toggle { display: none; }

.nav-drawer { position: fixed; inset: 0; z-index: 50; }
.nav-drawer-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.6); }
.nav-drawer-panel {
    position: absolute; top: 0; right: 0; bottom: 0; width: min(320px, 84vw);
    background: var(--bg-elevated); border-left: 1px solid var(--hairline);
    padding: 22px 24px; display: flex; flex-direction: column;
}
.nav-drawer-close { align-self: flex-end; margin-bottom: 24px; }
.nav-drawer-links { display: flex; flex-direction: column; gap: 4px; }
.nav-drawer-links a {
    font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; font-size: 24px;
    color: var(--text); padding: 14px 4px; border-bottom: 1px solid var(--hairline);
}
.nav-drawer-links a.active { color: var(--accent); }
.nav-drawer-login {
    margin-top: 24px; font-size: 12px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--text-muted); padding: 14px 4px;
}

@media (max-width: 767.98px) {
    .navbar { padding: 22px 18px 0; }
    .nav-links { display: none; }
    .nav-menu-toggle { display: flex; }
    .wordmark-group { gap: 8px; }
    .wordmark-mark { width: 17px; height: 17px; }
    .wordmark { font-size: 15px; }
}
```

### 6.2 Home page (`home.blade.php`)

The existing `version-2` branch of `home.blade.php` currently renders `<section class="hero-grid">` (the old 7-photo mosaic) followed by `@include('partials.hero-subnav', ...)`. Both of those are replaced. The album grid `<main>` block that follows is also replaced (it currently loops `$albums` into a Bootstrap `.row-cols-*` grid — that whole loop's *output structure* changes, but it's driven by the same `$albums` variable, same `@if ($albums->isEmpty())` guard).

**New markup, replacing everything from `@if (\App\Support\Theme::is('version-2'))` down to (but not including) `@include('partials.site-footer')`:**

```blade
@if (\App\Support\Theme::is('version-2'))
    @include('partials.hero-subnav', ['showLogin' => true])

    <div class="hero">
        <div class="hero-text">
            <h1>{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}</h1>
            @if (\App\Models\Setting::get('profile_bio'))
                <p class="bio">{{ \App\Models\Setting::get('profile_bio') }}</p>
            @endif
            <div class="stats">
                <strong>{{ number_format($totalPhotos) }}</strong> photos &nbsp;·&nbsp; <strong>{{ number_format($totalAlbums) }}</strong> albums
            </div>
        </div>
        @if ($heroPhoto)
            <div class="hero-feature">
                <a href="{{ route('albums.show', $heroPhoto->album) }}" class="hero-feature-img">
                    <img src="{{ route('photos.thumbnail', $heroPhoto) }}" alt="{{ $heroPhoto->album->name }}">
                </a>
                <div class="hero-feature-tag">{{ $heroPhoto->album->name }}</div>
            </div>
        @endif
    </div>

    <div class="ornament"><span></span><span></span><span></span></div>

    <main>
        @if ($albums->isEmpty())
            <div class="empty-state">
                <p class="mb-0">No albums to show yet.</p>
            </div>
        @else
            <div class="collage">
                @foreach ($albums as $index => $album)
                    <a class="tile {{ match(true) {
                        $index === 0 => 'g-big',
                        $index === 2 => 'g-tall',
                        $index === 4 => 'g-wide',
                        default => '',
                    } }}" href="{{ route('albums.show', $album) }}">
                        @if ($album->cover?->thumbnail_path)
                            <img src="{{ route('photos.thumbnail', $album->cover) }}" alt="{{ $album->name }}" loading="lazy" class="tile-img">
                        @else
                            <div class="tile-ph-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h6L16 8h4a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V9a1 1 0 011-1z"/><circle cx="12" cy="13" r="3.5"/></svg>
                            </div>
                        @endif
                        <div class="tile-overlay">
                            <div class="tile-name">{{ $album->name }}</div>
                            <div class="tile-count">{{ $album->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $album->photos_count) }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>

    <div class="ornament"><span></span><span></span><span></span></div>
@else
    {{-- existing default-theme branch: DO NOT TOUCH --}}
@endif
```

**Everything below this list is a decision this document is making explicitly, because the mockup — being static, hand-picked fake data — never had to answer it:**

1. **Which tiles get `g-big` / `g-tall` / `g-wide`?** Fixed by *array index*, not by any property of the album: index `0` is always `g-big`, index `2` is always `g-tall`, index `4` is always `g-wide`, everything else (including if there are fewer than 5 albums) is a plain `1×1` tile. This is simple, deterministic, and — because `$albums` is already ordered by `sort_order` then `date_taken desc` (see `HomeController`) — it means the admin's own top-priority-ordered album always gets the biggest tile. If there are fewer than 3 albums, `g-tall`/`g-wide` simply never get applied (the `match` expression's other arms just don't match) — verify this renders correctly with 1, 2, 4, and 9+ albums before considering this done.
2. **`$heroPhoto` (singular) is a new controller variable, not `$heroPhotos` (plural, 7 photos) from the old mosaic.** See Section 7.2 for the exact `HomeController` change. The view guards with `@if ($heroPhoto)` because a brand-new install with zero "ready" photos would otherwise error trying to call `->album` on `null` — **do not remove this guard**, it's not defensive-programming-for-no-reason, it's a real empty-state (a fresh site with no photos uploaded yet).
3. **`route('photos.thumbnail', $heroPhoto)`** — same route used for the collage tiles' `<img>` and for the original mosaic. Not a new route.

**CSS for this component:**

```css
.hero { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 36px; align-items: start; padding: 60px 40px 0 40px; }
.hero-text { padding-top: 64px; }
.hero-text h1 { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; font-size: 52px; line-height: 1.06; letter-spacing: -0.01em; margin: 0 0 22px; }
.hero-text .bio { font-size: 16px; line-height: 1.6; color: var(--text-muted); max-width: 380px; margin: 0 0 26px; }
.hero-text .stats { font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-tertiary); }
.hero-text .stats strong { color: var(--text); font-weight: 600; }

.hero-feature { position: relative; margin-top: -24px; margin-right: -40px; }
.hero-feature-img { display: block; aspect-ratio: 4/5; width: 100%; background: var(--bg-elevated); position: relative; overflow: hidden; }
.hero-feature-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.hero-feature-tag {
    position: absolute; left: -16px; bottom: 32px;
    background: var(--accent); color: var(--bg);
    font-size: 10.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    padding: 9px 16px; transform: rotate(-3deg);
    box-shadow: 0 10px 24px rgba(0,0,0,0.4);
    pointer-events: none; /* the tag is decorative; the whole .hero-feature-img is the actual link */
}

.collage { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 150px; gap: 4px; grid-auto-flow: dense; padding: 0 24px 56px; }
.g-big { grid-column: span 2; grid-row: span 2; }
.g-tall { grid-row: span 2; }
.g-wide { grid-column: span 2; }
.tile { display: block; position: relative; background: var(--bg-elevated); overflow: hidden; height: 100%; width: 100%; }
.tile-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tile-ph-icon { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: rgba(247,238,225,0.2); }
.tile-ph-icon svg { width: 24px; height: 24px; }
.g-big .tile-ph-icon svg { width: 34px; height: 34px; }
.tile-overlay { position: absolute; left: 0; right: 0; bottom: 0; padding: 14px; background: linear-gradient(to top, rgba(0,0,0,0.72), transparent); opacity: 0; transition: opacity .2s ease; }
.tile:hover .tile-overlay, .tile:focus-visible .tile-overlay { opacity: 1; }
.tile-name { font-size: 13px; font-weight: 600; color: #fff; }
.tile-count { font-size: 11px; color: rgba(255,255,255,0.72); margin-top: 2px; }

@media (max-width: 767.98px) {
    .hero { display: block; padding: 32px 22px 0; }
    .hero-text { padding-top: 0; }
    .hero-text h1 { font-size: 34px; line-height: 1.08; margin: 0 0 14px; }
    .hero-text .bio { font-size: 14.5px; margin: 0 0 16px; }
    .hero-feature { margin: 26px 0 0; padding: 0 18px; }
    .hero-feature-img { aspect-ratio: 4/3; }
    .hero-feature-tag { left: 14px; bottom: -12px; font-size: 10px; padding: 8px 14px; }
    .collage { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 120px; gap: 3px; padding: 0 16px 40px; }
}
```

One more thing the mockup's fixed-position tag couldn't show you: `.hero-feature-tag { left: -16px; }` on desktop deliberately bleeds *past* the left edge of the image (negative offset) for the "collage/overlap" effect. On mobile it switches to `left: 14px` (positive, inside the image) because at narrow widths there usually isn't a `40px` page margin to bleed into safely — verify in a real 375px-wide browser that the tag doesn't get clipped by the viewport edge.

### 6.3 About page (`pages/about.blade.php`)

Replace the `<header class="page-hero">...</header>` block (and the `@include('partials.hero-subnav', ...)` line right after it stays, just make sure it's still there) with:

```blade
@if (\App\Support\Theme::is('version-2'))
    @include('partials.hero-subnav', ['showLogin' => true])

    <div class="hero">
        <div class="eyebrow">{{ \App\Models\Setting::get('site_title') }}</div>
        <h1 class="large-title">{{ $title }}</h1>
    </div>
@else
    {{-- existing default-theme branch: DO NOT TOUCH --}}
@endif
```

The `<main>` block that follows keeps its existing `@if (filled($body)) ... @else ... @endif` structure exactly — only wrap the rendered body in the new classes:

```blade
<main class="page-main">
    @if (filled($body))
        <div class="body-text">
            {!! nl2br(e($body)) !!}
        </div>
    @else
        <div class="empty-state">
            <p class="mb-0">This page hasn't been written yet.</p>
        </div>
    @endif
</main>

<div class="ornament"><span></span><span></span><span></span></div>
```

**Why the drop-cap needs a real `<p>`, and why `nl2br(e($body))` is a problem for it:** the mockup's drop-cap CSS is `.body-text p:first-child::first-letter`, which requires the first block of text to actually be a `<p>` element. But `$body` is a single admin-editable textarea field rendered with `nl2br(e($body))` — meaning it's **one long string with `<br>` tags for line breaks, not separate `<p>` tags.** `::first-letter` still works fine on a `<div class="body-text">` directly (the CSS selector `.body-text p:first-child::first-letter` won't match because there's no `<p>` — but `.body-text::first-letter` applied directly to the div containing the raw `nl2br()` output *will* still target the very first character of the whole block). **Use `.body-text::first-letter` (targeting the div itself), not `.body-text p:first-child::first-letter`.** This is exactly the kind of detail that looks identical in a screenshot but breaks the moment you wire it to real (single-string, `<br>`-separated) data instead of the mockup's hand-written `<p>` tags — don't copy the mockup's CSS selector verbatim here.

**CSS:**

```css
.hero { max-width: 720px; margin: 0 auto; padding: 64px 40px 28px; text-align: center; }
.eyebrow { font-size: 11px; letter-spacing: 0.16em; text-transform: uppercase; color: var(--text-tertiary); margin: 0 0 14px; }
.large-title { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; font-size: 44px; line-height: 1.05; letter-spacing: -0.01em; margin: 0; }

.page-main { max-width: 640px; margin: 0 auto; padding: 36px 40px 64px; }
.body-text { font-size: 16px; line-height: 1.75; color: var(--text-muted); }
.body-text::first-letter { font-family: 'Cormorant Garamond', serif; font-size: 56px; font-weight: 600; color: var(--accent); float: left; line-height: 0.78; padding: 6px 8px 0 0; }

@media (max-width: 767.98px) {
    .hero { padding: 40px 22px 22px; }
    .large-title { font-size: 30px; }
    .page-main { padding: 28px 22px 40px; }
    .body-text { font-size: 15px; }
    .body-text::first-letter { font-size: 44px; padding: 4px 6px 0 0; }
}
```

Note: `.hero` and `.page-main` here are given **generic, page-scoped names** deliberately reused across About/Contact (both need "centered header block" + "centered content column" patterns) — but Home's `.hero` (Section 6.2) is a *different, incompatible* two-column grid layout using the same class name. **This will collide if all three pages' CSS live in the same global stylesheet, which they do** (`site-styles-version-2.blade.php` is one file included on every version-2 page). You must either (a) scope these with a body class (e.g. `<body class="page-about">` and prefix the selectors `.page-about .hero { ... }`), or (b) rename one set of classes so there's no collision (e.g. Home keeps `.hero`/`.hero-text`, About/Contact use `.page-hero`/`.page-eyebrow`/`.page-title` instead of reusing `.hero`/`.eyebrow`/`.large-title`). **Option (b) is simpler and less error-prone — use distinct class names per page rather than a body-class scoping scheme.** Concretely: rename this section's `.hero` → `.page-hero`, `.eyebrow` → `.page-eyebrow`, `.large-title` → `.page-title` in both the Blade markup above and the CSS above, and do the same in Section 6.5 (Contact uses the identical header pattern). This plan describes them as `.hero`/`.eyebrow`/`.large-title` above only to match the mockup's naming for readability — **rename before shipping**, and grep the final stylesheet for `.hero {` to confirm there's exactly one definition, not two conflicting ones.

### 6.4 Album / Gallery page (`albums/show.blade.php`)

This is the page that most directly matters to Ugis — it's the one explicitly modeled on the reference screenshot he provided (tight, edge-to-edge photo grid with a floating pill nav). Re-read ground rule #5 before touching this file: **the existing justified-row grid algorithm, infinite scroll, lightbox, and select/download JS are not being replaced.** What changes:

1. The `<header class="album-header">` block and the theme-conditional nav include, at the top.
2. The colors/radii of `.photo-tile`, `.photo-tile-checkbox`, `.photo-tile-download`, and everything inside `#lightboxModal` — cosmetic only.
3. One new element: the floating "subbar" (prev/next album, title, download shortcut), which is new markup sitting between the nav bar and the toolbar/grid.
4. The sub-album grid (children albums) markup gets restyled to match, but stays the simple existing per-child card loop — **do not** try to force it into the Home page's asymmetric collage treatment. The collage is a Home-page-specific flourish for a large curated set of albums; a handful of sub-albums doesn't need it, and forcing it would need the same "which index gets which span class" logic duplicated for no real benefit.

**Header replacement** — find the whole `@if (\App\Support\Theme::is('version-2')) ... @include('partials.hero-subnav', ...) @else` block near the top and replace the `@if` branch with:

```blade
@if (\App\Support\Theme::is('version-2'))
    @include('partials.hero-subnav', ['showLogin' => true])

    <div class="subbar-wrap">
        <div class="subbar">
            @if ($prevAlbum)
                <a class="subbar-btn" href="{{ route('albums.show', $prevAlbum) }}" aria-label="Previous album: {{ $prevAlbum->name }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
            @else
                <span class="subbar-btn subbar-btn-disabled" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </span>
            @endif

            <div class="subbar-title">
                <div class="name">{{ $album->name }}</div>
                <div class="count">
                    {{ $album->photos->count() }} {{ \Illuminate\Support\Str::plural('photo', $album->photos->count()) }}
                    @if ($album->date_taken) &middot; {{ $album->date_taken->format('M Y') }} @endif
                </div>
            </div>

            @if ($nextAlbum)
                <a class="subbar-btn" href="{{ route('albums.show', $nextAlbum) }}" aria-label="Next album: {{ $nextAlbum->name }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
            @else
                <span class="subbar-btn subbar-btn-disabled" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </span>
            @endif

            @if ($album->photos->isNotEmpty())
                <div class="subbar-divider"></div>
                <button type="button" class="subbar-btn" id="subbarDownload" aria-label="Select photos to download">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="M7.5 11.5L12 16l4.5-4.5"/><path d="M5 19h14"/></svg>
                </button>
            @endif
        </div>
    </div>

    @if ($album->description)
        <p class="album-description">{{ $album->description }}</p>
    @endif
@else
    {{-- existing default-theme branch: DO NOT TOUCH --}}
@endif
```

**Decisions this makes, spelled out:**

- **Prev/next only exists for top-level albums.** If `$album->isSubAlbum()` is true, `$prevAlbum` and `$nextAlbum` will both be `null` (see Section 7.1's controller code) — the disabled/greyed-out chevrons render, which is honest (there genuinely is no "next sub-album" concept in this design) rather than hiding the buttons and shifting the subbar's layout around depending on context. Keeping the disabled buttons visible (rather than removing them from the DOM) keeps the subbar's width/centering visually consistent whether you're on the first, last, or a middle album.
- **The "back link" that used to say `&larr; All albums` is gone**, replaced by the "Portfolio" nav-link (always visible in the main nav bar) serving the same purpose plus the prev/next chevrons. This is a deliberate simplification — don't add the old back-link back in as well, it would be redundant with both the nav bar and the subbar.
- **The download button (`#subbarDownload`) does not implement a new "download whole album in one click" feature.** Look closely at `GalleryController::downloadSelected()` — it validates `photo_ids` as `max:100`, and the existing toolbar's "Select Photos" → checkbox → "Download Selected" flow only ever operates on **currently-rendered** photo tiles (the grid loads in batches of 30 via infinite scroll — a photo the visitor hasn't scrolled to yet has no checkbox in the DOM at all). Building a true "select every photo in the album, including ones not yet rendered, respecting the 100-item cap, with correct UI feedback" is new scope beyond a visual redesign. **Decision: `#subbarDownload` is wired to trigger the exact same click handler as the existing "Select Photos" toolbar button** — it's a second, more prominent entry point into the *same* existing select-mode flow, not a new download mechanism. This keeps every existing safeguard (the 100-item cap, the per-click zip generation) intact untouched. Add this to the bottom of the existing `<script>` block in this file (near the other `toggleBtn` wiring, not as a separate script tag):

```js
const subbarDownloadBtn = document.getElementById('subbarDownload');
if (subbarDownloadBtn && toggleBtn) {
    subbarDownloadBtn.addEventListener('click', () => toggleBtn.click());
}
```

  This must be added *after* `toggleBtn` is defined (it already is, near the top of the existing script) — place this new block right after the existing `if (toggleBtn) { ... }` block that wires up `toggleBtn`'s own click handler, so `toggleBtn` is guaranteed to exist by the time this code runs.
- **The existing "Select Photos" / "Cancel" / "Download Selected (N)" toolbar row (the `<div class="toolbar">` with `#toggleSelect` and `#downloadSelectedBtn`) stays exactly where it is, unchanged in markup, only restyled in CSS.** The subbar button is a shortcut *to* it, not a replacement *of* it — a visitor who clicks the subbar icon should see the exact same toolbar UI respond (button text flips to "Cancel", checkboxes appear) as if they'd clicked the original button, because it *is* the original button being clicked programmatically.

**CSS for the subbar and the restyled photo grid** (add to `site-styles-version-2.blade.php`; this file's own `<style>` block at the top of `albums/show.blade.php` handles the layout-*structural* CSS which you leave alone — see the list right after this):

```css
.subbar-wrap { display: flex; justify-content: center; padding: 36px 40px 24px; }
.subbar {
    display: flex; align-items: center; gap: 2px;
    background: var(--bg-elevated); border: 1px solid var(--hairline);
    border-radius: 999px; padding: 6px; height: 56px;
}
.subbar-btn { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--text-muted); flex-shrink: 0; border: 0; background: none; }
.subbar-btn:hover { background: var(--accent-soft); color: var(--accent); }
.subbar-btn-disabled { color: var(--text-tertiary); opacity: 0.4; pointer-events: none; }
.subbar-btn svg { width: 17px; height: 17px; }
.subbar-title { padding: 0 18px; display: flex; flex-direction: column; align-items: center; min-width: 220px; max-width: 360px; }
.subbar-title .name { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; font-size: 17px; letter-spacing: 0.02em; line-height: 1.25; text-align: center; }
.subbar-title .count { font-size: 10.5px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-tertiary); margin-top: 2px; white-space: nowrap; }
.subbar-divider { width: 1px; height: 24px; background: var(--hairline); margin: 0 2px; }
.album-description { max-width: 640px; margin: 0 auto 24px; padding: 0 24px; text-align: center; font-size: 14px; color: var(--text-muted); }

@media (max-width: 767.98px) {
    .subbar-wrap { padding: 24px 18px 20px; }
    .subbar { height: 52px; padding: 5px; }
    .subbar-btn { width: 40px; height: 40px; }
    .subbar-title { min-width: 130px; max-width: 200px; padding: 0 10px; }
    .subbar-title .name { font-size: 15px; }
}
```

Note `.subbar-title .name` uses `line-height: 1.25; text-align: center` and no `white-space: nowrap`/`text-overflow: ellipsis` — a long album name is allowed to **wrap to two lines** rather than being truncated with `...`. The album's actual name is meaningful information (not decoration); silently cutting it off would hide real content. This does mean the subbar's height is only reliably `56px`/`52px` when the name fits one line — if you see two-line names in testing, that's expected and fine, don't "fix" it by forcing `nowrap` + ellipsis.

**Cosmetic-only vs. structural CSS in the existing `<style>` block at the top of `albums/show.blade.php`** — go through it rule by rule:

| Rule | Cosmetic (restyle freely) | Structural (do not touch) |
|---|---|---|
| `.album-header`, `.album-header h1`, `.album-header .meta`, `.album-header .back-link` | — | Delete entirely (this whole block is replaced by the new header markup above, which has no `.album-header` wrapper at all) |
| `.toolbar` | padding/spacing — restyle to match new tokens | — |
| `.photo-grid`, `.photo-row` | — | **Do not touch.** `.photo-row { display: flex; gap: 6px; margin-bottom: 6px; }` is load-bearing for the justified-row algorithm — the JS reads/writes `flex-grow`/`flex-basis`/`width` on the actual tile elements based on this container being a flex row. Changing `gap` here changes the algorithm's math (the JS's `containerWidth` calculation assumes a specific gap), so if you want different spacing between photos, you must change it **consistently in both the CSS `gap: 6px` and the JS's row-building math** — or, simpler, just leave it at `6px`, it's a reasonable value and matches the rest of the grid gaps used elsewhere in this redesign closely enough. |
| `.photo-tile` | `border-radius`, `background-color` — change to `var(--bg-elevated)` (already is) and pick a radius consistent with the rest of the redesign (e.g. `0` or `2px` — the reference screenshot's grid has sharp/near-sharp corners, not the `0.6rem` the current file uses) | `position: relative`, `overflow: hidden`, `cursor: pointer` — structural, keep |
| `.photo-tile-img` | — | Keep as-is, it's just `width/height/object-fit/transition`, already correct |
| `.photo-tile-checkbox` | Recolor the checked-state SVG fill / border color to the new accent if you want the checkmark tinted — optional | The `position: absolute`, `display: none` / `.select-mode` toggle mechanism — do not touch, this is how the JS's select-mode CSS class controls visibility |
| `.photo-tile-download` | Recolor background/blur to match new tokens if desired | Position/sizing/`opacity` transition mechanics — keep |
| `#lightboxModal .modal-content`, `.modal-header`, `#lightboxCounter`, buttons, `.btn-close`, `#lightboxImg`, `.lightbox-nav`, `.lightbox-tap-zone` | All of it — recolor to match new tokens (the file's own comment already calls this "iOS/macOS-style", it's meant to look like a native frosted-glass viewer; keep that character, just swap `var(--brand)`/`var(--accent)` references to the new token names) | The actual `position`, `z-index`, `width`/`height` numbers for tap zones and nav buttons — these are sized for real touch-target and swipe-detection purposes, don't shrink them below what's there now |

**Sub-album grid restyle** — the `@if ($album->children->isNotEmpty())` loop keeps its exact Blade structure (it's not part of the "collage" system), just restyle the existing `.album-card`, `.album-thumb`, `.album-card-body`, `.album-title`, `.album-meta` classes (defined in `site-styles-version-2.blade.php`, shared with the old Home page grid) to the new tokens — same treatment as a single plain `.tile` from Section 6.2, minus the `.tile-overlay` hover-reveal pattern (keep the sub-album cards' existing always-visible caption-below-thumbnail style, since that's a different, perfectly fine pattern and there's no reason to change it just because Home changed).

### 6.5 Contact page (`pages/contact.blade.php`)

This is the page with the most real functional state to preserve. Read this section fully before writing any markup — the mockup's static HTML is missing several `name`/`value`/`required` attributes that are load-bearing for the form actually working, because a visual mockup has no reason to include them.

Replace the header the same way as About (Section 6.3 — same `.page-hero`/`.page-eyebrow`/`.page-title` classes, same reasoning about the naming collision, reuse it here rather than defining a third variant):

```blade
@if (\App\Support\Theme::is('version-2'))
    @include('partials.hero-subnav', ['showLogin' => true])

    <div class="page-hero">
        <div class="page-eyebrow">{{ \App\Models\Setting::get('site_title') }}</div>
        <h1 class="page-title">{{ $title }}</h1>
    </div>
@else
    {{-- existing default-theme branch: DO NOT TOUCH --}}
@endif
```

Replace the `<main>` block's contents (the two-column `row`/`col-lg-7`/`col-lg-5` Bootstrap grid with the card-form and social links) with:

```blade
<main class="contact-main">
    <div class="contact-columns">
        <div class="contact-col-form">
            @if (session('status'))
                <div class="form-status form-status-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="form-status form-status-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('contact.submit') }}">
                @csrf

                {{-- Honeypot — unchanged from the previous implementation, copied verbatim. Do not remove or alter. --}}
                <div style="position: absolute; left: -9999px;" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="field">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="name" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" required>
                </div>
                <div class="field">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" required>{{ old('message') }}</textarea>
                </div>

                <button class="btn-send" type="submit">
                    Send Message
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>
        </div>

        <div class="contact-col-side">
            @if (filled($body))
                <p class="contact-body">{!! nl2br(e($body)) !!}</p>
            @endif

            @if (!empty($socialLinks))
                <div class="socials">
                    @foreach ($socialLinks as $link)
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="social-row">
                            <span class="social-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 6 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-6-3.8-9s1.3-6.5 3.8-9z"/></svg>
                            </span>
                            <span class="social-label">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</main>

<div class="ornament"><span></span><span></span><span></span></div>
```

**Every one of these is a deliberate, specific decision — read before changing any of them:**

1. **The submit button is `type="submit"` inside the `<form>`, not `type="button"` like the mockup's static HTML.** The mockup had no real form to submit, so it used `type="button"` to avoid a dead-end submit in a demo. The real page needs `type="submit"` or the form will never POST.
2. **`name="name"`, `name="email"`, `name="message"` attributes are present** — the mockup only had `id` attributes (fine for a non-functional visual demo, useless for an HTTP form — the server reads `$request->input('name')` etc. by `name`, not `id`). Also `value="{{ old('name') }}"` etc. so a validation failure re-populates what the visitor already typed, and `required` + `autocomplete="name"`/`"email"` for basic native browser validation/autofill — all present in the *original* pre-redesign contact form, all preserved here.
3. **The honeypot block is copied verbatim, unchanged**, including its inline `style="position: absolute; left: -9999px;"` — don't try to move this into the stylesheet as a class, and don't change the field name from `website`. `StoreContactMessageRequest` and `PageController::submitContact()`'s `$request->filled('website')` check depend on that exact field name.
4. **`.form-status-success` / `.form-status-error` are new classes — the mockup never showed these states because a static mockup has no server round-trip.** You're designing their look fresh, following the established token system: a warm, low-contrast filled banner rather than Bootstrap's default green/red alert boxes. Suggested CSS (adjust to taste, but keep it in the same visual family as everything else — no bright saturated colors):

```css
.form-status { border-radius: 12px; padding: 14px 18px; font-size: 14px; margin-bottom: 28px; }
.form-status-success { background: var(--accent-soft); color: var(--accent); }
.form-status-error { background: var(--danger-soft); color: var(--danger); }
.form-status-error ul { margin: 0; padding-left: 18px; }
```
5. **The social row is deliberately monochrome with a single generic "link" icon for every platform, dropping the current implementation's per-platform brand-colored Bootstrap Icons (`bi-instagram` in Instagram pink, `bi-facebook` in Facebook blue, the Instagram gradient text-clip treatment, etc.).** This is a real, visible behavior change from what's live today, made deliberately as part of the new monochrome-plus-one-accent design direction — it is **not** an oversight or a corner cut for engineering convenience. It also sidesteps a real engineering cost: the current `$socialMeta` array in the old `contact.blade.php` hand-maps about 18 platform names to specific Bootstrap Icon classes and hex colors; recreating that as 18 hand-drawn custom SVGs (since this redesign doesn't use Bootstrap Icons at all — Section 4.4) would be a large amount of icon-drawing work disproportionate to a visual-redesign task. **If Ugis wants per-platform icons back after seeing this, that's a scoped follow-up (pick which platforms actually matter, draw or source that specific smaller set of icons), not something to silently half-implement now.** The label text (`{{ $link['label'] }}`) still identifies the platform by name next to the generic icon, so no information is lost, only the color/icon differentiation.
6. **`$errors` and `session('status')` are Laravel globals already available in every Blade view** — you do not need to pass them from the controller, and `PageController::contact()` doesn't need any changes for this to work (it already didn't pass them, because Blade provides them automatically after a redirect-with-errors or redirect-with-flash-session).

**CSS:**

```css
.page-hero { max-width: 720px; margin: 0 auto; padding: 64px 40px 8px; text-align: center; }
.page-eyebrow { font-size: 11px; letter-spacing: 0.16em; text-transform: uppercase; color: var(--text-tertiary); margin: 0 0 14px; }
.page-title { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; font-size: 44px; line-height: 1.05; letter-spacing: -0.01em; margin: 0; }

.contact-main { max-width: 900px; margin: 0 auto; padding: 36px 40px 0; }
.contact-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: start; }

.field { margin-bottom: 30px; }
.field label { display: block; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-tertiary); margin-bottom: 10px; }
.field input, .field textarea {
    width: 100%; background: transparent; border: 0; border-bottom: 1px solid var(--hairline);
    color: var(--text); font-size: 16px; font-family: inherit; padding: 0 0 10px; outline: none;
}
.field input:focus, .field textarea:focus { border-bottom-color: var(--accent); }
.field textarea { resize: none; min-height: 70px; line-height: 1.5; }
.field input::placeholder, .field textarea::placeholder { color: var(--text-tertiary); }

.btn-send {
    display: inline-flex; align-items: center; gap: 8px; background: none; border: 0;
    color: var(--text); font-size: 12px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    padding: 14px 0; font-family: inherit; margin-top: 6px; cursor: pointer;
}
.btn-send:hover { color: var(--accent); }

.contact-body { font-size: 15px; line-height: 1.65; color: var(--text-muted); margin: 0 0 32px; }

.socials { display: flex; flex-direction: column; }
.social-row { display: flex; align-items: center; gap: 14px; padding: 14px 4px; border-bottom: 1px solid var(--hairline); color: var(--text-muted); }
.social-row:hover { color: var(--accent); }
.social-icon { display: flex; width: 20px; height: 20px; flex-shrink: 0; }
.social-icon svg { width: 100%; height: 100%; }
.social-label { font-size: 14px; font-weight: 500; }

@media (max-width: 767.98px) {
    .page-hero { padding: 40px 22px 8px; }
    .page-title { font-size: 30px; }
    .contact-main { padding: 28px 22px 0; }
    .contact-columns { grid-template-columns: 1fr; gap: 40px; }
    .btn-send { display: flex; justify-content: center; width: 100%; height: 48px; border: 1px solid var(--accent); border-radius: 999px; color: var(--accent); margin-top: 8px; }
}
```

Note the mobile `.btn-send` override turns the desktop's plain text-link-style button into a full-width bordered pill — this is a deliberate mobile-specific adaptation (a thumb-sized tappable button reads better than a small inline text link on a touchscreen), matching what the mockup's mobile artboard showed, not an inconsistency to "fix" into matching desktop exactly.

### 6.6 Footer

`partials/site-footer.blade.php` itself does not need to change — its content logic (`Setting::get('footer_text')` with `{year}` replacement) is fine as-is. Only its *appearance* needs new CSS, since the redesign removes footer's `border-top` in favor of the `.ornament` divider placed directly above the `<footer>` include on every page (already shown in each page's markup above):

```css
footer { padding: 0 24px 40px; text-align: center; color: var(--text-tertiary); font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; }
@media (max-width: 767.98px) {
    footer { padding: 0 18px 36px; font-size: 10.5px; }
}
```

### 6.7 Empty states

`.empty-state` is already used in multiple places (`home.blade.php`, `pages/about.blade.php`, `albums/show.blade.php`) with existing markup `<div class="empty-state"><p class="mb-0">...</p></div>` — just restyle the class, don't change any of the call sites:

```css
.empty-state { padding: 80px 24px; text-align: center; color: var(--text-tertiary); font-size: 15px; }
```

---

## 7. Backend changes required

Exactly two controller methods change. Nothing else in `app/` changes for this redesign.

### 7.1 `GalleryController::show()` — add previous/next top-level album

**Current code:**

```php
public function show(Album $album): View
{
    $album->load([
        'photos' => fn ($query) => $query->where('status', 'ready')
            ->orderByRaw('CASE WHEN sort_order > 0 THEN 0 ELSE 1 END ASC')
            ->orderBy('sort_order')
            ->orderByRaw('COALESCE(captured_at, created_at) ASC')
            ->orderBy('original_filename'),
        'children' => fn ($query) => $query->withCount('photos')->with('cover'),
    ]);

    return view('albums.show', ['album' => $album]);
}
```

**New code:**

```php
public function show(Album $album): View
{
    $album->load([
        'photos' => fn ($query) => $query->where('status', 'ready')
            ->orderByRaw('CASE WHEN sort_order > 0 THEN 0 ELSE 1 END ASC')
            ->orderBy('sort_order')
            ->orderByRaw('COALESCE(captured_at, created_at) ASC')
            ->orderBy('original_filename'),
        'children' => fn ($query) => $query->withCount('photos')->with('cover'),
    ]);

    [$prevAlbum, $nextAlbum] = $this->siblingAlbums($album);

    return view('albums.show', [
        'album' => $album,
        'prevAlbum' => $prevAlbum,
        'nextAlbum' => $nextAlbum,
    ]);
}

/**
 * Previous/next album for the subbar's prev/next controls, using the same
 * ordering as the home page's album grid (sort_order, then date_taken
 * desc — see HomeController::index()). Only defined for top-level albums:
 * a sub-album has no "next sub-album" concept in this design, so both
 * come back null when $album->isSubAlbum() and the view renders disabled
 * arrows instead of links.
 *
 * @return array{0: ?Album, 1: ?Album}
 */
private function siblingAlbums(Album $album): array
{
    if ($album->isSubAlbum()) {
        return [null, null];
    }

    $topLevelAlbums = Album::query()
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->orderByDesc('date_taken')
        ->get(['id', 'slug', 'name', 'sort_order', 'date_taken']);

    $currentIndex = $topLevelAlbums->search(fn (Album $a) => $a->is($album));

    if ($currentIndex === false) {
        // Shouldn't happen (the album we're showing should be in its own
        // top-level list), but fail safe rather than throwing.
        return [null, null];
    }

    return [
        $currentIndex > 0 ? $topLevelAlbums[$currentIndex - 1] : null,
        $currentIndex < $topLevelAlbums->count() - 1 ? $topLevelAlbums[$currentIndex + 1] : null,
    ];
}
```

Don't forget the `use App\Models\Album;` import is already present in this file (it's used in the method signature already) — no new imports needed beyond what's already there.

**Why `->is($album)` instead of `$a->id === $album->id`:** `Model::is()` is Laravel's built-in "same database row" comparison and handles edge cases (like comparing against a model with a null key) more defensively than a raw `id` comparison — use it, it's not being fancy for no reason, it's the standard idiom.

**Why re-query `Album::query()->whereNull('parent_id')->...->get(...)` instead of reusing `$albums` from `HomeController`:** this is a *different* controller and a *different* request — `GalleryController::show()` has no access to `HomeController::index()`'s local variable. This is simply the same query logic, duplicated because it's needed in two places. If this bothers you, it could be extracted into a method on the `Album` model (e.g. `Album::topLevelOrdered()`) and called from both controllers — that's a reasonable follow-up refactor, not required for this plan to work, and not something to do as a surprise addition without flagging it.

### 7.2 `HomeController::index()` — reduce hero photos from 7 to 1

**Current code:**

```php
'heroPhotos' => Theme::is('version-2')
    ? Photo::where('status', 'ready')->whereNotNull('thumbnail_path')->inRandomOrder()->limit(7)->get()
    : collect(),
```

**New code:**

```php
'heroPhoto' => Theme::is('version-2')
    ? Photo::where('status', 'ready')->whereNotNull('thumbnail_path')->with('album')->inRandomOrder()->first()
    : null,
```

Note the **variable name changes from `heroPhotos` (plural, a collection) to `heroPhoto` (singular, a single model-or-null)** — this matches the `@if ($heroPhoto)` guard used in Section 6.2's Blade code, and matches `first()` instead of `limit(7)->get()`. **Grep the whole codebase for `heroPhotos`** after making this change to confirm nothing else references the old plural variable name (it was only ever used inside `home.blade.php`'s now-replaced hero-grid markup, but confirm rather than assume).

`->with('album')` is added because the new hero markup calls `$heroPhoto->album->name` and `route('albums.show', $heroPhoto->album)` — without eager-loading, this still *works* (Eloquent lazy-loads on access) but issues an extra query. Since this is a single row on the homepage, it's added for correctness/good practice, not because skipping it would break anything.

---

## 8. Explicitly out of scope — do not touch

Stated plainly so nothing here gets "helpfully" changed as a side effect:

- The `default` theme (anything a `Theme::is('version-2')` check's `@else` branch renders) — completely unrelated visitors on that theme must see zero change.
- The admin dashboard (`resources/views/admin/**`, admin routes/controllers).
- Auth pages (`resources/views/auth/**`), the `dashboard.blade.php`, `profile/**` — none of these are part of the public marketing/gallery experience this plan covers.
- `tailwind.config.js`, `vite.config.js`, `package.json` — no build tooling changes.
- Any database migration, any model's `$fillable`/schema.
- The iCloud sync feature, photo upload feature, or anything else under `admin/`.
- The justified-row layout algorithm, infinite-scroll batching, lightbox navigation/preloading, and select+zip-download logic in `albums/show.blade.php`'s `<script>` block (ground rule #5 — restated here because it's the single easiest thing to accidentally "clean up" while you're in that file for the CSS changes).
- `Setting::socialLinks()`'s data shape, `StoreContactMessageRequest`'s validation rules, `ContactMessage` model — the redesign only changes how existing data is *displayed*.

---

## 9. Recommended build order

Doing this in a different order will work too, but this order minimizes the amount of time the site is in a half-migrated, visually broken state, and lets you catch mistakes early rather than after wiring everything together:

1. **Confirm the theme setting.** Check `Setting::get('theme')` is `'version-2'` in the actual database before you start (Section 1, rule 1) — if it isn't, none of your changes will even render, and you'll waste time debugging the wrong thing.
2. **Replace `site-styles-version-2.blade.php` entirely** (Sections 4 + all the CSS blocks from Section 6, assembled into one file) — do this first, before touching any page markup. The site will look broken (old markup, new colors/fonts) until step 3–6 catch up, but at least the tokens/fonts/reusable snippets exist in one place from here on.
3. **Replace `hero-subnav.blade.php`** (Section 6.1) — every page that includes it will immediately start using the new nav bar.
4. **Home page** (Section 6.2 + the `HomeController` change from 7.2) — do the controller change and the view change together, in the same step, since the view depends on the new `$heroPhoto` variable existing.
5. **About page** (Section 6.3) — simplest page, good sanity check that the shared tokens/classes are working before tackling the more complex pages.
6. **Contact page** (Section 6.5) — pay close attention to the `name=`/`value=`/`required` attributes called out explicitly; test an actual form submission (both success and a deliberate validation failure, e.g. submit with an invalid email) before considering this page done.
7. **Album/Gallery page** (Section 6.4 + the `GalleryController` change from 7.1) — do the controller change first, verify `$prevAlbum`/`$nextAlbum` are correct in a quick `dd()` or log statement for a few different albums (first, middle, last, a sub-album) before wiring up the view, then do the view/CSS changes.
8. **Full pass through Section 10's checklist**, on both desktop and mobile widths, for all four pages.

---

## 10. Verification checklist

Go through this for real, in a browser — not by reading the code and assuming it's fine. This project has no automated visual test coverage for these pages, so this checklist is the only safety net.

**Every page, both ≥768px and a real ~390px-wide mobile viewport (or actual device/emulator):**
- [ ] Nav bar renders correctly; on mobile, hamburger opens the drawer, drawer's links work, Escape and backdrop-click both close it.
- [ ] Active nav-link state is correct on each of the four pages (including: is an individual album page still highlighting "Portfolio" as active?).
- [ ] Login icon still links to `route('login')`.
- [ ] Every social icon link (nav bar + Contact page) opens the admin-configured URL in a new tab.
- [ ] Grain texture is visible but subtle, and doesn't block any clicks anywhere (try clicking through it deliberately on a few elements).
- [ ] Footer text renders correctly (check the `{year}` placeholder actually resolves to the current year).

**Home page specifically:**
- [ ] With a normal number of albums (5+): collage grid renders with the big/tall/wide tiles in the right spots, no gaps, no overlap.
- [ ] With exactly 1 album, exactly 2 albums, exactly 4 albums: collage still renders sensibly (no broken `g-tall`/`g-wide` referencing a tile that doesn't exist).
- [ ] With **zero** albums: empty-state message shows, no PHP error.
- [ ] With **zero** "ready" photos anywhere on the site (fresh install case): hero section doesn't error, `.hero-feature` block simply doesn't render (guarded by `@if ($heroPhoto)`).
- [ ] An album with **no cover photo** set: its collage tile shows the camera placeholder icon, not a broken image.
- [ ] Stats numbers (`$totalPhotos`, `$totalAlbums`) match reality.

**Album/Gallery page specifically:**
- [ ] Justified-row photo grid still lays out correctly, infinite scroll still loads more photos near the bottom.
- [ ] Lightbox: opens on tile click, prev/next buttons work, arrow keys work, swipe works on a touch device/emulator, "Full Size" and "Download" links in the lightbox toolbar work.
- [ ] "Select Photos" toolbar button still toggles select mode; the new subbar download icon also toggles it (clicking either one flips both into the same state).
- [ ] Selecting checkboxes and submitting downloads a correctly-named zip.
- [ ] Prev/next subbar arrows: on the first top-level album, the "previous" arrow is visibly disabled and does nothing; on the last, "next" is disabled; on a middle album, both work and land on the correct neighboring album (cross-check the order against what's shown on the Home page grid — it must match, since both are driven by the same `sort_order`/`date_taken` ordering).
- [ ] On a **sub-album** page: both prev/next arrows are disabled (this is correct, not a bug).
- [ ] An album with a very long name: subbar wraps to two lines rather than being clipped, and doesn't break the subbar's layout.
- [ ] Sub-album grid (when the album has children) still renders and links correctly.
- [ ] An album with **zero photos** and **zero children**: empty-state message shows, no PHP error, no broken toolbar.

**About page specifically:**
- [ ] Drop-cap renders on the actual admin-entered body text (not just the placeholder used during development) — specifically check a body text that starts with a lowercase letter, and one that starts with a non-letter character (a number or punctuation), to see the drop-cap still looks acceptable in both cases.
- [ ] Empty body text (`about_body` setting blank): "This page hasn't been written yet." shows correctly.

**Contact page specifically:**
- [ ] Submit a valid message: success banner shows, message actually lands in the database (check `ContactMessage` table or the admin messages list — but do not go into the admin UI to redesign anything, just verify the data arrived).
- [ ] Submit with a missing/invalid field: validation error banner shows with the actual error text, and the fields you *did* fill in are still populated (not wiped).
- [ ] Confirm the honeypot still works as before (this is hard to test manually since it's meant to fool bots, not humans — at minimum, confirm the hidden field still renders in the page source with the right `name="website"` and is visually off-screen).
- [ ] Zero social links configured: the whole `.socials` block simply doesn't render (no empty box).
- [ ] `contact_body` setting blank: the sidebar paragraph simply doesn't render (`@if (filled($body))` guard), no empty paragraph tag.

---

*End of plan. If anything above turns out to be wrong once you're actually in the code (a route name that's changed, a field that doesn't exist), stop and flag it rather than silently improvising around it — this document was written from a real read of the current codebase, but "verified once during planning" is not the same guarantee as "still true when you get there."*
