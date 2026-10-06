# Photo-portfolio — Project Instructions

Auto-loaded at the start of every Claude Code session in this repo. Keep it current; it is the single source of truth for how to work here.

## What this project is

A **personal photo archive** Ugis is proud to show people. Secondary goals: occasionally landing photography client work, and serving as a dev-skills learning vehicle. It is **not** a revenue project.

**Out of scope — do not build or suggest:** e-commerce, payments, checkout, print ordering.

## Who you're working with

Ugis works **entirely alone** — sole developer, designer, product owner, and sysadmin. No teammates, no reviewer, no handoff. Multiple app accounts are all his own, for testing roles.

Consequence: you are the only second pair of eyes. Flag risks, name what's unverified, don't rubber-stamp. Never suggest team processes ("have a teammate review this").
Experience: Act, like you are a senior developer with 30+ years of experience, who just left the biggest company in world, to help Ugis develop this project. Only suggest what is logical and needed. 

## How to work

- **Autonomy**: free rein on local code changes. Ask before anything touching production or hard to reverse.
- **Check in before starting any new feature or task** — confirm the plan before writing code.
- **Check in midway through long tasks** — periodic progress, not one big reveal at the end.
- **Reporting: very terse.** One or two sentences on the result. No padded walkthroughs unless asked, only option surveys.

### Hard limits — never without explicit go-ahead
1. **Never push directly to `github`.** Ugis will branch and push everything himself, even though he works solo.
2. **Never modify DB data directly.** Schema or data changes need confirmation first.
3. **Never apply changes on the live server** (`ssh admin@photos-ip`) without asking — there are no backups.

## Stack (verified from the repo, not from memory)

*Last verified against the live VPS on 2026-07-21. The Database and Mail rows were both wrong before that date — re-check against `.env` rather than trusting this table indefinitely.*

| Layer | What |
|---|---|
| Backend | Laravel `^13.8`, PHP `^8.3`, Artisan (`./artisan`) |
| Frontend | Blade + Alpine.js `^3.4`, Tailwind `^3.1`, Vite `^8` |
| Database | **MySQL** — `DB_CONNECTION=mysql`, database `portfolio`. (A stale `database/database.sqlite` from 2026-07-05 is still in the repo and is *not* in use — ignore it.) |
| Storage | `FILESYSTEM_DISK=local` — images on local disk, no S3/CDN. Disk root is `storage_path('app/private')`. |
| Queue | `QUEUE_CONNECTION=database`. One `queue:work` process runs on the VPS. Run `php artisan queue:restart` after deploying job classes — the worker holds stale code in memory. |
| Scheduler | Installed: `* * * * * cd /var/www/portfolio && php artisan schedule:run`. Tasks live in `routes/console.php` via the `Schedule` facade. |
| Mail | **`MAIL_MAILER=log` in production — mail does NOT send.** Verified 2026-07-21. Contact form submissions are stored in the DB only; nothing reaches anyone by email. `MAIL_FROM_ADDRESS` is still the placeholder `hello@example.com`. Any feature that depends on notifying someone is blocked until a real mailer is configured. |
| Containers | `Dockerfile` + `docker-compose.yml` |

**Commands**: `npm run dev` · `npm run build` · `./artisan …` · `./artisan test`

**Note**: Tailwind `^3.1` and `@tailwindcss/vite` v4 are both present — a version mismatch worth watching if styles behave oddly.

## Infrastructure

- **Live**: http://ugis.id.lv on a self-managed VPS (`ssh admin@photos-ip`).
- **No staging.** The VPS is production. Branches/worktrees are the only pre-prod layer.
- **No backups of any kind** — no DB dumps, no snapshots, nothing scheduled. Any server-side mistake is unrecoverable.
- **Secrets**: `.env` on the server, plus a password manager. Not consolidated.
- **Disaster exposure**: photo originals are safe (iCloud, independent of the app). What would be lost is site-only data — activity logs, admin config, contact form submissions.

## Roles

`masteradmin` and `admin` have meaningfully different permissions. Confirmed: **masteradmin can add/remove user accounts, admin cannot.** Other differences may exist in code — verify rather than assume.

## Visitor data

The contact form **stores name, email, and message in the DB**. A privacy notice reportedly exists, but its wording/placement is unverified — check the page before making any compliance claim.

## Known rough edges

- **Responsive/mobile layout** is the main known bug area. Treat it as the fragile zone for any layout change.
- **Tests**: `phpunit.xml`, `tests/Feature/Admin/`, `tests/Feature/Auth/`, `tests/Feature/ProfileTest.php` all exist. Ugis believes there are no tests and doesn't use them. Run the suite to learn its real state — don't claim tests are absent, and don't assume they're green.
- Stack choices were inherited/grew organically. No attachment to them; stack changes are a fair topic.

## Current priorities (in order)

1. **iCloud → site photo sync** (one-way; the iCloud folder is the source of truth). Today, uploads through the site are only for testing. Implementation plan: **`docs/icloud-sync-plan.md`** — read it before starting any sync work.
2. **Fix known bugs / tech debt** — starting with responsive layout.
3. **Public-facing design & UX polish** — home, about, contact.

Admin dashboard polish is explicitly *lower* priority than the above.

## Verification

No trusted automated safety net exists in practice. When a change is UI-affecting, either verify it in a browser or say plainly that it's unverified. Never imply something works when it hasn't been checked.

---

## Persistent memory

Longer-lived and cross-session facts live in a per-project memory directory, indexed by `MEMORY.md` there. That directory is the place for evolving knowledge; this file is the stable contract. When you learn something durable and non-obvious, write it to memory — and if it changes the rules above, update this file too.

**The path differs by machine** — use whichever exists where you are running:

| Machine | Memory directory |
|---|---|
| Mac (`/Users/ugis/Projects/Photo-portfolio`) | `~/.claude/projects/-Users-ugis-Projects-Photo-portfolio/memory/` |
| VPS (`/var/www/portfolio`) | `~/.claude/projects/-var-www-portfolio/memory/` |

Standing rules currently recorded there: work is scoped to the **`version-2`** theme only (2026-07-18), and landing-page chats must present a plan table before executing and must not edit the admin dashboard.
