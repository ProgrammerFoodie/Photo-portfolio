# Contact-Sheet Redesign — Implementation Plan

**Status:** Draft, not yet approved. Not deployed, not committed.
**Audience:** Whoever (human or LLM) implements this — assume no memory of the design conversation that produced it.
**Design reference:** Three working prototypes, each a self-contained HTML file published as a Claude Artifact:

| Prototype | What it covers |
|---|---|
| "Ugis Photography" landing page | Home page: hero, services ticker, portfolio overview grid, process, closing CTA, contact |
| "Autumn Wedding" album + viewer | A single album's full photo grid (native aspect ratios, no crop) and the full-screen photo viewer opened from it |

These are the source of truth for exact visual behavior — colors, type, spacing, motion, the grease-pencil hover marks, the sprocket-hole rails. Where this document and a prototype disagree, or the prototype is ambiguous, this document wins, because it accounts for real data and existing functionality a static/demo prototype didn't have to deal with.

## 0. Where this fits

This session tried **two different visual directions** for the public-site redesign. The first — a warm-espresso, editorial-minimal look — has its own plan already sitting in this repo at `docs/redesign-implementation-plan.md`. Ugis then saw a different concept (a photographer's lightbox/contact-sheet review session — bright ground, grease-pencil marks, sprocket-hole rails, monospace+condensed-display type) and said he liked that direction better, then asked for it to be built out into real pages.

**This document describes the contact-sheet direction and treats it as the one to actually build.** `docs/redesign-implementation-plan.md` is still in the repo but should be treated as superseded — don't implement both, and don't mix tokens/components from the two documents on the same page. Ugis should decide whether to delete or archive the older plan; this document doesn't do that on its own.

Only two pages have an actual design to build from: **Home** and **Album** (which includes the full-screen viewer as part of the same page). About and Contact have not been designed in this direction at all — see Section 6, which is a scoping note, not a design.

---

## 1. Ground rules

Same project-wide constraints as before, restated because they still apply:

1. **Scope is the `version-2` theme only**, gated by `App\Support\Theme::is('version-2')` (backed by `Setting::get('theme')`). Every change lives inside that conditional's branch, or in a file only ever included when it's active. Never touch the `default`-theme partials or the `@else` branches. Confirm the theme is actually set to `version-2` in the database before starting — don't assume.
2. **Do not touch the admin dashboard** (`resources/views/admin/**`, the `admin` route group, admin controllers).
3. **No database migrations for the parts of this plan that are just a visual reskin.** One genuinely new field of data is used (real photo width/height for aspect ratio) and it already exists — see Section 4. The one place a real product decision is needed (Section 5, the "mark as keeper" feature) is called out explicitly as client-side-only, specifically to avoid needing a migration; if that decision changes, revisit this rule.
4. **No new JavaScript framework, no build-tool changes.** Same as before: plain `<script>` tags, vanilla JS, the existing Bootstrap-CSS-and-JS-via-CDN setup that `version-2` already uses. Google Fonts is the only external font source the site's CSP-equivalent conventions rely on.
5. **Do not touch `albums/show.blade.php`'s existing justified-row-grid, infinite-scroll, and lightbox JavaScript.** This rule matters even more here than in the other plan: the Contact Sheet prototype's "no crop, no white space" photo grid uses **the same technique** that file's JS already implements (flex-grow proportional to aspect ratio, zero flex-basis). Section 5 explains exactly why you should reskin the existing engine rather than port the prototype's own copy of that algorithm.
6. **Mobile is this project's known fragile area.** Verify everything at real widths in a real browser, not just by reading the code.
7. **This document itself must not be deployed anywhere** — it's a planning artifact for `docs/`, nothing more.

---

## 2. Design system reference

Everything in this section is shared across every page — define it once, reuse it everywhere. Given how much CSS the `version-2` theme already concentrates into `partials/site-styles-version-2.blade.php`, keep doing that: this whole design system lives in that one file (a full replacement of its current contents, same as the other plan would have done, just with different values).

### 2.1 Color tokens

```css
:root{
  /* A darkroom lightbox, not a webpage: cool-neutral near-white ground,
     near-black ink, one grease-pencil red used the way an editor
     actually uses one — to circle keepers, and to mark the one thing a
     visitor should do. This is a single committed visual world (a
     physical lightbox doesn't have a "dark mode"), so unlike the other
     plan's dark/light-aware tokens, this design intentionally has ONE
     theme, no prefers-color-scheme branch. Every color is still declared
     explicitly (never left to inherit/transparency) so the page holds up
     regardless of the viewer's OS theme. */
  --lightbox:#f2f4f1;
  --ink:#15181a;
  --ink-soft:rgba(21,24,26,0.62);
  --ink-faint:rgba(21,24,26,0.38);
  --grease:#a91f28;
  --grease-soft:rgba(169,31,40,0.12);
  --rule:rgba(21,24,26,0.16);
}
```

No `--bg-elevated`-style surface tokens exist in this system, unlike the other plan — cards/panels here are just `#fff` with a `1px solid var(--rule)` border (see the "selected-frames bar" in Section 5), not a tinted elevated background. Don't invent one without a real need.

### 2.2 Typography

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
```

- **Display face:** `'Anton','Arial Narrow',sans-serif` — a single-weight, very heavy, condensed poster face. Used for headlines and page titles only, always uppercase, always with `text-transform:uppercase` (Anton doesn't have real lowercase-vs-uppercase distinction worth relying on — force uppercase in CSS rather than typing content in caps, so admin-entered text still gets the treatment).
- **Body/UI face:** `'IBM Plex Mono','SFMono-Regular',Menlo,Consolas,monospace` — every label, caption, nav item, button, and body paragraph in this design. This is a deliberate, load-bearing choice (the "technical/exposure-data" feel is a big part of what makes this design distinctive) — **do not swap it for a proportional font anywhere**, including body copy on About/Contact once those get designed.
- Do not add a third font. If a heavier/lighter Plex Mono weight is needed somewhere, add that weight to the URL rather than assuming the browser will fake it.

### 2.3 Sprocket rails (persistent chrome, every page)

Two fixed bars, top and bottom, standing in for a film strip's perforations. The top one doubles as a live "frame counter" tracking scroll position through whatever's numbered on the current page. This is the site's **entire top-level navigation chrome replacement** — there is no traditional navbar in this design; the identity strip (Section 2.4) sits below the top rail instead.

```css
.rail{ position:fixed; left:0; right:0; height:22px; z-index:50; background:var(--ink); display:flex; align-items:center; }
.rail.top{ top:0; } .rail.bottom{ bottom:0; }
.rail .holes{ flex:1; align-self:stretch; background-image:repeating-radial-gradient(circle at 11px 11px, var(--lightbox) 0 4px, transparent 4px 22px); background-size:22px 22px; }
.rail .counter{ flex-shrink:0; padding:0 14px; height:100%; display:flex; align-items:center; gap:8px; background:var(--ink); color:var(--lightbox); font-size:11px; font-weight:600; letter-spacing:0.08em; }
.rail .counter .n{ color:#e8a4a8; }
```

```html
<div class="rail top">
  <div class="counter"><span>FRAME</span><span class="n" id="frameCounter">00</span><span>/{{ $totalCount }}</span></div>
  <div class="holes"></div>
</div>
<div class="rail bottom"><div class="holes"></div></div>
```

`main` (or whatever wraps the page's real content) needs top/bottom padding clearing `22px` so the fixed rails never overlap real content — the prototypes handle this with `main{ padding:44px 0 0; }`, i.e. roughly double the rail height for comfortable clearance, plus whatever bottom padding the page's own last section already has.

**The frame counter needs a real "what am I counting" answer per page** — the prototypes count grid tiles via `IntersectionObserver`:

```js
var countObserver = new IntersectionObserver(function(entries){
  entries.forEach(function(entry){
    if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
      counterEl.textContent = entry.target.dataset.frame;
    }
  });
}, { threshold: [0.5] });
tiles.forEach(function(el){ countObserver.observe(el); });
```

Each numbered element needs `data-frame="01"` etc. On Home, this counts album tiles; on Album, it counts photo frames. **Decide what About/Contact count before designing them** (Section 6) — don't ship a rail with a counter that never updates on a page with nothing numbered on it; either give it something real to count or drop the counter (keep the holes) on pages where nothing is naturally numbered.

### 2.4 Identity strip

Sits directly below the top rail. This is the actual site-wide nav (replaces `partials/hero-subnav.blade.php`'s role entirely — that partial's *content* changes completely, its *include call sites* on all four page templates stay the same).

```blade
@php
    $navItems = [
        ['route' => 'home', 'matches' => ['home', 'albums.show'], 'label' => 'Portfolio'],
        ['route' => 'about', 'matches' => ['about'], 'label' => 'About'],
        ['route' => 'contact', 'matches' => ['contact'], 'label' => 'Contact'],
    ];
@endphp
<div class="identity">
    <a class="mark" href="{{ route('home') }}">{{ \App\Models\Setting::get('profile_handle') ?: \App\Models\Setting::get('site_title') }}</a>
    <nav>
        @foreach ($navItems as $item)
            <a class="{{ request()->routeIs(...$item['matches']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
</div>
```

```css
.identity{ display:flex; align-items:baseline; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:0 clamp(20px,5vw,56px); margin-bottom:clamp(32px,6vh,64px); }
.identity .mark{ font-size:13px; font-weight:600; letter-spacing:0.14em; }
.identity nav{ display:flex; gap:22px; }
.identity nav a{ font-size:11px; letter-spacing:0.1em; text-transform:uppercase; color:var(--ink-soft); padding:4px 0; }
.identity nav a:hover, .identity nav a.active{ color:var(--grease); }
```

**This is different from what the two landing-page/album prototypes actually shipped** — both of those hardcoded the nav as `Work / Process / Contact` with `href="#"` placeholder links, because they were single-page demos with in-page anchors, not a multi-route site. **For the real site, use `Portfolio / About / Contact` pointing at real named routes**, exactly like the other plan's nav did. "Process" (Home's how-it-works section, Section 3) stays as an in-page section people scroll to; it does not need its own top-level nav slot. This is a deliberate correction from the prototypes, not an oversight — flagging it because copying the prototype's nav labels verbatim would leave the real About page unreachable from the header.

### 2.5 Grease-pencil mark

The signature interaction: a hand-drawn double-loop circle that draws itself on (via `stroke-dashoffset`), used for (a) the one "hero"/featured item on a page, drawn automatically shortly after load, and (b) any grid tile, drawn on hover/focus. `pathLength="100"` on each `<ellipse>` normalizes the dash math regardless of the actual ellipse geometry — don't try to compute real circumference.

```css
.grease-mark{ position:absolute; inset:-9%; width:118%; height:118%; pointer-events:none; overflow:visible; }
.grease-mark ellipse{ fill:none; stroke:var(--grease); stroke-width:2.4; stroke-linecap:round; stroke-dasharray:100; stroke-dashoffset:100; transition:stroke-dashoffset .6s cubic-bezier(.3,.7,.2,1); }
.grease-mark ellipse:nth-child(2){ transition-delay:.1s; stroke-width:2; }
/* Trigger classes: add `.is-marked` to the ancestor for an on-load draw,
   or rely on :hover/:focus-visible for a hover-triggered draw. */
.some-hero-el.is-marked .grease-mark ellipse{ stroke-dashoffset:0; }
.some-tile:hover .grease-mark ellipse, .some-tile:focus-visible .grease-mark ellipse{ stroke-dashoffset:0; }
```

```html
<svg class="grease-mark" viewBox="0 0 100 100" aria-hidden="true">
  <ellipse cx="50" cy="50" rx="46" ry="39" pathLength="100" transform="rotate(-7 50 50)"></ellipse>
  <ellipse cx="51" cy="49" rx="44" ry="41" pathLength="100" transform="rotate(6 50 50)"></ellipse>
</svg>
```

This SVG is an **original hand-drawn shape**, not a recreation of any real brand's mark — keep it exactly as-is, don't "clean it up" into a perfect circle (the slightly offset double-ellipse is what reads as hand-marked).

### 2.6 Film-grain texture (on photo placeholders only — see the correction below)

```css
.some-photo-el::after{
  content:''; position:absolute; inset:0; opacity:0.15; mix-blend-mode:overlay; pointer-events:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size:120px 120px;
}
```

**Correction versus the other plan:** the warm-espresso direction applied a grain overlay to the *whole page background*. This direction does not — the `--lightbox` ground stays perfectly flat and clean (that flatness is part of what makes it read as a lightbox rather than a mood-lit room). Grain here is applied **only as a texture on top of individual photo elements** (real `<img>` tags once wired to real data — layer this `::after` on a wrapping `position:relative` element around the `<img>`, not on the `<img>` itself, since an `img` element can't reliably host a `::after`). Don't add a page-wide grain layer to this design; it isn't part of the direction.

### 2.7 Justified, no-crop photo grid

This is the technique behind "every photo at its real aspect ratio, and every row fills edge to edge with no leftover white space" — the exact thing Ugis asked for and then corrected (via an artifact comment) when the first pass left ragged row-ends. **The real codebase's `albums/show.blade.php` already implements this technique** (see the `layoutJustified()` function and its surrounding comments in that file) — the description below is given so you recognize it as the same algorithm and reskin it, per ground rule 5, rather than replacing it with a second copy.

The core trick: group photos into rows by measuring container width against a target row height; then, for **every** row including the last one, give each tile `flex-grow: <its aspect ratio>; flex-shrink:1; flex-basis:0;` inside a `display:flex; align-items:flex-start` row container, with each tile's own `aspect-ratio` CSS property set to its real `width/height`. Flex-grow proportional to aspect ratio splits the row's full width in exact proportion to each photo's aspect ratio; because every tile in the row ends up with the same "width per unit of aspect," and each tile's height is then derived from *its own* `aspect-ratio` property against *its own* resolved width, every tile in the row lands on the same height automatically — without ever setting a row height directly. Applying this to **every** row (not just "full" ones, and not leaving the last row at its natural, possibly-short width) is what eliminates white space at the start or end of the grid, which is exactly the fix Section 5's prototype needed.

---

## 3. Step 1 — Landing page (Home)

**File:** `resources/views/home.blade.php` (the `version-2` branch only).

### 3.1 What it's built from

The published "Ugis Photography" landing-page prototype, sections top to bottom: sprocket rails, identity strip, hero (headline + one grease-pencil-marked featured photo), a horizontally-scrolling services ticker, a portfolio overview grid ("the roll"), a four-step process list, a closing call-to-action band, and a contact block.

### 3.2 Real-data mapping — read this before wiring anything up

The prototype used entirely fabricated content (a fixed roster of 24 fake "albums" framed as one 24-exposure roll, static service names, hardcoded process copy). Mapping it to the real site needs a few corrections the prototype had no reason to get right on its own:

1. **The hero.** One large featured photo + a rotated tag label, same pattern as the other plan's `$heroPhoto` (see Section 7.2 — identical controller change, reuse it verbatim, it isn't direction-specific). Guard with `@if ($heroPhoto)` for the zero-photos case.
2. **The portfolio grid represents `$albums` (real, variable count) — not a fixed 24, and not "one roll."** The prototype's "ROLL 014 · 24 EXPOSURES" framing describes a *single shoot*, which is exactly what the Album page (Step 2) represents — it does not make sense applied to an overview spanning many different albums/shoots. **Drop the "one roll" framing on this grid specifically.** Use real stats instead:
   ```blade
   <div class="sheet-head">
       <h2>The work</h2>
       <div class="roll">{{ number_format($totalAlbums) }} ALBUMS &middot; {{ number_format($totalPhotos) }} FRAMES</div>
   </div>
   ```
   `$totalAlbums`/`$totalPhotos` are the same variables `HomeController::index()` already passes to the view today — no controller change needed for this part.
3. **Grid tiles are album covers, square-cropped, one tile per `$album`** — the same `@if ($album->cover?->thumbnail_path)` / placeholder-icon pattern the other plan specified, looped over the real `$albums` collection instead of a fixed list. Because this grid represents *albums* (not the raw uncropped photos a specific shoot contains), cropping the cover thumbnail to a uniform tile size here is fine and does **not** conflict with the "no crop" requirement — that requirement is specifically about the Album-open view (Step 2), where you're looking at one shoot's actual photos.
4. **Frame numbers on this grid are just `01`, `02`, …, sequential through `$albums`, not tied to anything else.** Feed the same numbers into the top rail's counter (Section 2.3).
5. **The services ticker is static marketing copy, not admin-configurable** — there's no Settings field for "list of service categories," and adding one is out of scope for a visual reskin. Hardcode it in the Blade template (`Weddings · Portraits · Families · Editorial · Everyday Light`, or whatever Ugis actually wants to advertise — ask him for the real list rather than guessing). If he wants this admin-editable later, that's a small, separate follow-up (one new Setting key, `services_ticker`, comma-split in the view) — don't build that speculatively now.
6. **The four-step "process" list is static copy**, same reasoning — it describes how Ugis works, not per-visitor data. Use the exact copy from the prototype as a starting draft; Ugis should review/edit the wording before this ships, the same way any other marketing copy would get a review pass.
7. **The closing contact block uses a real email, not `[YOUR EMAIL]`.** The prototype deliberately left this as a visibly-marked placeholder rather than inventing a fake address (see the earlier plan's identical rule about not fabricating contact facts) — replace it with Ugis's real contact email before this ships. If a public email isn't wanted, swap the block for a link to the real `route('contact')` page instead of an inline `mailto:`.

### 3.3 CSS

Port the prototype's CSS for `.hero`, `.hero-text`, `.hero-feature*`, `.ticker*`, `.sheet-head`/`.sheet`/`.tile` (or, if you'd rather reuse one grid component across Home and Album, rename to avoid clashing with Album's own grid classes — same naming-collision caution as the other plan's `.hero` clash between Home and About/Contact), `.process*`, `.cta-band`, and `#contact` blocks verbatim from the published artifact — they're already tuned and don't need re-deriving. Add the standard responsive breakpoint (Section 3.4).

### 3.4 Responsive

Single breakpoint, `768px`, matching the other plan's convention for consistency across the codebase. At minimum: hero goes single-column (headline above the photo), the ticker's font size and gap shrink, the grid's implicit column count (governed by the justified-row target height, Section 2.7) drops its target height for narrower tiles, and the process list's numeral column narrows. Copy the exact `@media (max-width:767.98px)` block from the prototype and adjust only if real testing shows a problem — don't invent new breakpoint values.

---

## 4. Step 2 — Album view

**File:** `resources/views/albums/show.blade.php` (the `version-2` branch, plus the shared `<script>` block at the bottom of the file — read carefully, most of it is untouched).

### 4.1 What changes and what doesn't

Ground rule 5 is the headline here: **the existing justified-row grid, infinite-scroll batching, and lightbox JavaScript already in this file are not being replaced.** The "Autumn Wedding" prototype's own JS (`computeRows`/`renderRows`) is a **second, independent implementation of the same technique** the real file already has — it exists in the prototype because the prototype is a standalone demo with no Laravel backend to fetch real photos from, not because the real site needs a new layout engine. Do not port the prototype's JS into `albums/show.blade.php`. Instead:

1. Keep the real file's existing `layoutJustified()`, `renderNextBatch()`, infinite-scroll `IntersectionObserver`, and lightbox modal logic exactly as they are.
2. Restyle `.photo-tile`, `.photo-row`, `.photo-grid`, and the `#lightboxModal` chrome to the Contact Sheet tokens (Section 4.3) — this is the same "cosmetic vs. structural" pass the other plan already specified rule-by-rule for this same file; that table still applies here, just aimed at different final colors.
3. Add the pieces that are genuinely new: the sprocket rails + identity strip (shared chrome, Section 2), the album header with prev/next album (Section 4.2), per-tile frame numbers (Section 4.4), and the grease-pencil hover mark + "mark as keeper" feature (Section 5 — big enough to be its own step, since it's really the full-screen viewer's feature, surfaced back into the grid).

### 4.2 Album header — prev/next album

Same feature, same reasoning, same controller code as the other plan (Section 7.1 below is a verbatim copy) — a photographer's "which shoot is this, and what's next" framing fits this design even better than the last one, since it's now dressed as "roll" metadata instead of a plain page title.

```blade
<div class="roll-head">
    <a class="back-link" href="{{ route('home') }}">&larr; All work</a>
    <div class="roll-title-row">
        @if ($prevAlbum)
            <a class="album-nav" href="{{ route('albums.show', $prevAlbum) }}" aria-label="Previous album: {{ $prevAlbum->name }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            </a>
        @else
            <span class="album-nav album-nav-disabled" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            </span>
        @endif

        <div class="roll-title">
            <div class="eyebrow">{{ $album->date_taken?->format('F Y') ?? 'Undated' }}@if($album->location) &middot; {{ $album->location }} @endif</div>
            <h1 class="display">{{ $album->name }}</h1>
        </div>

        @if ($nextAlbum)
            <a class="album-nav" href="{{ route('albums.show', $nextAlbum) }}" aria-label="Next album: {{ $nextAlbum->name }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        @else
            <span class="album-nav album-nav-disabled" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </span>
        @endif
    </div>
</div>
```

Same edge-case rules as the other plan: only defined for top-level albums (a sub-album shows both arrows disabled), disabled arrows stay in the DOM (greyed out, `pointer-events:none`) rather than being removed, so the header's centering doesn't shift depending on context.

### 4.3 CSS restyle — the cosmetic/structural table

Identical table to the other plan's Section 6.4, same file, same rules about what's safe to restyle (`.photo-tile` radius/background, `#lightboxModal`'s colors/blur) versus what's structural and must not change (`.photo-row`'s `display:flex; gap:6px`, the `select-mode` class toggle mechanism, tap-zone sizing). Re-read that table in `docs/redesign-implementation-plan.md` Section 6.4 rather than duplicating it here verbatim — the only thing different this time is which token values you're plugging in (Section 2.1's lightbox palette instead of the warm-espresso one), and the addition of frame numbers (next section), which that table didn't cover because the other design didn't have them.

One new note specific to this direction: the real `#lightboxModal` already looks like a dark, frosted-glass theatrical viewer (its own comment in the file says "iOS/macOS-style fullscreen viewer") — that's already extremely close to this design's full-screen viewer concept (Section 5). Recoloring its `var(--brand)`/`var(--accent)` references to `var(--grease)` and its background to the near-black `#0a0b0a` used in the prototype gets you most of the way to Section 5's look for free.

### 4.4 Frame numbers — use real photo order, not fabricated aspect ratios

The prototype invented 24 photos with hand-picked aspect ratios and wedding-moment captions, purely because it had no real data to draw from. **The real implementation must not invent aspect ratios** — `Photo` already has `width` and `height` columns, and the existing JS already computes `aspect: ($photo->width && $photo->height) ? $photo->width / $photo->height : (4/3)` when it builds `ALBUM_PHOTOS` (see the `<script type="application/json" id="albumPhotosData">` block already in this file). That's the real aspect ratio — use it as-is, don't recompute or override it.

Frame numbers are simply the photo's 1-based position in `$album->photos` (the same already-sorted collection the file loops over today — sorted by pinned `sort_order` then capture date, per `GalleryController::show()`). Add a `data-frame` attribute when each tile is created in the existing `createTileEl(photo)` JS function:

```js
tile.dataset.frame = String(index + 1).padStart(2, '0'); // `index` is this photo's position in ALBUM_PHOTOS
```

and a small always-visible number label in the tile's corner (new markup inside `createTileEl`, styled per Section 2's `.tile-no` pattern) — **do not invent per-photo captions** ("GETTING READY", "FIRST LOOK", etc.) for real albums; the prototype's wedding-moment labels were flavor text for a fake demo, real photos don't have that metadata available and shouldn't be captioned with guesses.

---

## 5. Step 3 — Full-screen viewer

**File:** same file as Step 2, `resources/views/albums/show.blade.php` — this is the existing `#lightboxModal`, reskinned, plus one new feature layered on top of it.

### 5.1 What's a reskin vs. what's new

- **Reskin (covered by Section 4.3's table):** the modal chrome, colors, blur, close button, nav arrows, tap zones, counter pill, swipe/keyboard handling, preloading — all of it already exists and already works. Don't rebuild it from the prototype's version; the prototype's viewer is a from-scratch reimplementation of the same idea, built only because the demo had no Laravel modal/JS to attach to.
- **New: "Mark keeper."** This is the one genuinely new interactive feature in the whole redesign, and it needs an explicit product decision before it's built for real — read this whole subsection before writing any code for it.

### 5.2 The decision this needs

Ugis's own comment on the prototype asked for "an option to contact me to download images." The prototype answered that with a client-side "mark as keeper" toggle in the viewer, which flags that photo back in the grid and surfaces a running count with a "contact me about these" call to action. That's a **different interaction model** from what the site already has: today, a visitor can already self-serve a zip download of any photos they select, with no need to contact Ugis at all (`GalleryController::downloadSelected()`).

**These two are not mutually exclusive, and the decisive recommendation here is to keep both, side by side, rather than replace one with the other:**

- The existing "Select Photos" → checkbox → "Download Selected" flow stays exactly as it is (ground rule 5 already requires this).
- "Mark keeper" is a **separate, additive, lightweight layer** for visitors who want to flag favorites for Ugis to see/discuss, without necessarily wanting an instant self-serve zip — e.g. a couple reviewing their wedding gallery and wanting to say "these are our favorites, can we talk about prints" rather than just downloading originals unprompted.

If Ugis would rather these be unified into one flow (e.g. "mark keeper" *becomes* the selection mechanism, and "download selected" *becomes* "contact about selected"), that's a real, different scope — flag it back to him rather than silently picking one interpretation over the other once you're actually building this.

### 5.3 Implementation, given the "keep both, additive" decision

**No backend, no database.** "Kept" state lives only in a JS variable for the current page load (`kept = {}`, keyed by photo id) — refreshing the page loses it, same as the prototype. Persisting this server-side (a real "client favorites" feature, tied to a session or a login) is a meaningfully bigger feature than a visual redesign and is out of scope here; don't build it speculatively.

In the lightbox's existing JS (near `showLightboxPhoto`/`openLightbox`), add:

```js
var kept = {};

function toggleKeep(photoId) {
    kept[photoId] = !kept[photoId];
    var tile = grid.querySelector('.photo-tile[data-photo-id="' + photoId + '"]');
    if (tile) { tile.classList.toggle('is-kept', !!kept[photoId]); }
    updateKeeperBar();
}

function updateKeeperBar() {
    var ids = Object.keys(kept).filter(function (id) { return kept[id]; });
    var bar = document.getElementById('keeperBar');
    var countEl = document.getElementById('keeperCount');
    if (!bar || !countEl) { return; }
    countEl.textContent = ids.length;
    bar.hidden = ids.length === 0;
    var contactLink = document.getElementById('keeperContact');
    if (contactLink) {
        contactLink.href = '{{ route('contact') }}?photos=' + ids.join(',');
    }
}
```

Add a "Mark keeper" button to the existing `#lightboxModal .modal-header` (next to "Full Size"/"Download"), styled per Section 2.5's grease-mark hover pattern for the corresponding grid tile, plus a small persistent bar below the grid (hidden until `kept` has at least one entry) — same visual pattern as the prototype's `.selects-bar`.

**The "contact me about these" link is real and functional, not a placeholder**, using a technique the prototype couldn't (it had no real Contact page to link to): it deep-links to `route('contact')` with a `?photos=1,7,14`-style query string. On the real Contact page (`pages/contact.blade.php`, once it exists in this direction — Section 6), add a small inline script that pre-fills the message textarea if that query parameter is present:

```js
var params = new URLSearchParams(window.location.search);
var photos = params.get('photos');
if (photos) {
    var textarea = document.getElementById('message');
    if (textarea && !textarea.value) {
        textarea.value = 'Hi! I\'d love copies of these photos from ' + document.title.split('·')[0].trim() + ': frame(s) ' + photos.split(',').join(', ') + '.';
    }
}
```

This keeps the whole "keeper" feature genuinely useful (it ends in a real, working contact submission that already lands in `ContactMessage`, no new backend at all) without inventing a bigger feature than was asked for.

---

## 6. Step 4 — About & Contact (not designed yet)

**Stop here if you were about to improvise these.** Nothing in this document — or in the three published prototypes — shows what About or Contact look like in the contact-sheet direction. Building them "in the spirit of" the rest of this system without an actual design pass would mean guessing at things a real design decision should settle: does About get a hero photo and a grease-pencil mark, or is it pure typography? Does the Contact form get styled as a caption/label sheet (extending the mono/technical language) or something else? What does the sprocket-rail counter track on a page with nothing to count?

**Before implementing these two pages:** go back through the same design process the other three pages went through — a quick mockup pass, a look at it, a correction round — rather than shipping a first guess as final. The Contact page does have one hard requirement already established regardless of visual treatment: the `?photos=` pre-fill script from Section 5.3 needs to land in it, whatever its final layout looks like.

---

## 7. Backend changes required

Two of these are identical to the other plan (same problem, same fix, direction-independent) — reproduced here so this document is self-contained.

### 7.1 `GalleryController::show()` — previous/next top-level album

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
 * Previous/next album for the header's prev/next controls, using the same
 * ordering as the home page's album grid (sort_order, then date_taken
 * desc). Only defined for top-level albums — a sub-album has no "next
 * sub-album" concept in this design, so both come back null and the view
 * renders disabled arrows.
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
        return [null, null];
    }

    return [
        $currentIndex > 0 ? $topLevelAlbums[$currentIndex - 1] : null,
        $currentIndex < $topLevelAlbums->count() - 1 ? $topLevelAlbums[$currentIndex + 1] : null,
    ];
}
```

### 7.2 `HomeController::index()` — single hero photo

```php
'heroPhoto' => Theme::is('version-2')
    ? Photo::where('status', 'ready')->whereNotNull('thumbnail_path')->with('album')->inRandomOrder()->first()
    : null,
```

Renamed from the plural `heroPhotos` (was a 7-photo collection for the old mosaic) to singular `heroPhoto` (one model or `null`) — grep the codebase for the old name after making this change to confirm nothing else references it.

### 7.3 No change needed for the "mark as keeper" → Contact pre-fill

Covered fully in Section 5.3 — it's front-end only (a query string plus a few lines of JS on the Contact page), `PageController::submitContact()` needs no changes at all since the pre-filled textarea submits through the exact same form as always.

---

## 8. Explicitly out of scope

- The `default` theme, the admin dashboard, auth pages, `dashboard.blade.php`, `profile/**` — unrelated to this redesign, same as the other plan.
- Any build-tool/Tailwind/Vite change.
- Persisting "kept" photos server-side, or tying them to a session/login — Section 5.3 keeps this client-side-only and in-memory on purpose.
- A CMS field for the services ticker or process copy — hardcode it for now (Section 3.2, items 5–6); revisit only if Ugis specifically wants it admin-editable later.
- Designing About/Contact — Section 6 is a scoping note, not a green light to freehand them.
- The justified-row layout algorithm, infinite scroll, and lightbox core logic in `albums/show.blade.php` — reskin only, per ground rule 5.

---

## 9. Recommended build order

1. Confirm `Setting::get('theme')` is actually `'version-2'` in the real database.
2. Replace `site-styles-version-2.blade.php` with the Section 2 design system (tokens, fonts, rails, identity strip, grease-mark, grid technique notes) plus each step's page-specific CSS as you reach it below — same "tokens first" approach as the other plan.
3. Replace `partials/hero-subnav.blade.php`'s content with the Section 2.4 identity-strip markup (route-based nav, not the prototypes' anchor-based one).
4. **Step 1 — Home**: controller change (7.2) + view change (Section 3), together since the view depends on the new `$heroPhoto` variable.
5. **Step 2 — Album**: controller change (7.1) + the cosmetic CSS pass (Section 4.3) + the album header (4.2) + frame numbers (4.4). Do the CSS-only cosmetic pass first and verify the existing grid/lightbox/infinite-scroll still all work *before* adding the album header or frame numbers, so a regression is easy to isolate.
6. **Step 3 — Viewer**: the "mark as keeper" feature (Section 5.3) — confirm with Ugis on the "additive, not a replacement" decision (5.2) before writing this part.
7. Stop. Steps 1–3 are everything this document actually specifies. Don't improvise Step 4 (About/Contact) — go get a real design for them first (Section 6).

---

## 10. Verification checklist

**Every page:**
- [ ] Sprocket rails render top and bottom, content has enough padding to never sit under them.
- [ ] Identity strip nav is `Portfolio / About / Contact` pointing at real routes, with correct active-state highlighting on each page (including: does an individual album page still highlight "Portfolio"?).
- [ ] Frame counter in the top rail tracks something real as you scroll, or is intentionally absent if there's nothing to count.

**Home:**
- [ ] Hero renders with a real photo when one exists, and doesn't error when the site has zero "ready" photos.
- [ ] Portfolio grid header reads real `$totalAlbums`/`$totalPhotos` counts, not "24 exposures."
- [ ] Grid has one tile per real album, correct cover image or placeholder icon, correct link.
- [ ] Zero albums: empty state shows, no PHP error.
- [ ] Services ticker and process copy have been reviewed/edited by Ugis, not shipped as prototype placeholder text.
- [ ] Contact block at the bottom has a real email or links to the real Contact page — no `[YOUR EMAIL]` placeholder left in shipped copy.

**Album:**
- [ ] Justified grid still has zero white space at the start or end of every row, at a real range of viewport widths — this was the exact bug Ugis flagged on the prototype; check it didn't come back.
- [ ] Every photo's on-screen aspect ratio matches its real `width`/`height` — no forced squares, no stretching.
- [ ] Frame numbers count up in the same order photos are actually sorted in (pinned first, then capture date).
- [ ] No fabricated per-photo captions anywhere.
- [ ] Prev/next album: correct on first, middle, last, and sub-album cases (same checks as the other plan's Section 10).
- [ ] Existing select/download/infinite-scroll/lightbox functionality all still work, unchanged, after the CSS reskin.

**Viewer:**
- [ ] "Mark keeper" toggles correctly, updates the grid tile, updates the running count bar.
- [ ] The count bar is hidden when zero photos are kept, appears once at least one is.
- [ ] "Contact me about these" link lands on the real Contact page with the message field pre-filled with the correct frame numbers.
- [ ] Refreshing the page clears "kept" state (confirms it's client-side-only, as designed) — if Ugis expected this to persist, that's the Section 5.2 decision resurfacing and needs to go back to him, not get silently "fixed."

---

*End of plan. If something here turns out to be wrong once you're actually in the code, stop and flag it rather than improvising around it — this was written from a real read of the codebase and the three published prototypes, but that's not a guarantee it's still accurate by the time you're implementing it.*
