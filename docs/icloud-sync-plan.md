# iCloud → Site Photo Sync

**Implementation plan. Written to be executed by an LLM agent with no prior context.**

Repo: `/var/www/portfolio` (branch `main`) — this path **is production**. Priority #1 per `CLAUDE.md`.

Revision 2 — 2026-07-21. Supersedes revision 1.

---

## Status — implemented 2026-07-21

Phases 1–3 are **built, tested and migrated on production**. Nothing is syncing yet: no shared album has been linked.

| Built | Where |
|---|---|
| Protocol client | `app/Services/ICloud/SharedAlbumClient.php` |
| Sync orchestration | `app/Services/ICloud/SharedAlbumSyncService.php`, `SyncResult.php` |
| Queued job | `app/Jobs/SyncICloudAlbumJob.php` |
| Commands | `icloud:link`, `icloud:sync` (`--dry-run`, `--force`) |
| Schedule | Hourly `icloud:sync` in `routes/console.php` |
| Migrations | Both applied to production MySQL (backup: `.backups/portfolio-20260721-192531.sql.gz`) |
| Tests | `tests/Feature/ICloud/` — 20 tests, all passing |

| Admin UI | `ICloudAlbumController`, `admin/albums/link.blade.php`, iCloud column on the albums index, iCloud panel on album edit |

Deviations from the plan as written:

- **§6.3** — `activity_logs.user_id` is `NOT NULL` with an FK, so system rows would have needed a third migration the plan did not sanction. Sync history goes to a dedicated `icloud` log channel (`storage/logs/icloud-sync.log`, 30 days) instead.
- **§7 admin UI** — approved and built on 2026-07-21. Pruning is deliberately **not** exposed in the UI; it stays a command-line action.

### Everything imports through the queue — do not "simplify" this

`storage/app/private/photos` is owned by `www-data` mode `0700`. The scheduler's crontab belongs to `admin`, and php-fpm runs as `www-data`. So:

- `icloud:sync` **enqueues** by default and the worker does the downloading. Running the import in-process from cron fails on every file write with permission denied.
- `--now` runs inline and only works when invoked as `www-data`:
  `sudo -u www-data php artisan icloud:sync 3 --now`
- `--dry-run` is read-only and safe as any user.
- The admin "Link" and "Sync now" buttons dispatch jobs for the same reason, plus a large first import would exceed the request timeout.

**After deploying job classes, run `php artisan queue:restart`** — the worker holds stale code in memory.

**Test suite baseline:** 5 pre-existing failures (`AlbumManagementTest::test_authenticated_user_can_view_dashboard`, two in `RegistrationTest`, `AuthenticationTest::test_users_can_authenticate_using_the_login_screen`, `ExampleTest`). They fail because `DashboardStatsService` emits a `HAVING` clause SQLite rejects, while production is MySQL. Unrelated to sync, present before this work, still present after — do not mistake them for a regression.

**To start syncing:** `php artisan icloud:link 'https://www.icloud.com/sharedalbum/#B0...'` — or Admin → Albums → Link iCloud Album.

### Proven against real Apple endpoints, 2026-07-21

First real album imported: 21 photos, all `ready`, thumbnails built, zero failures. A second run created 0 duplicates, confirming the unique index does its job.

What the live API actually does — several details differ from the reverse-engineered notes above:

| Observation | Detail |
|---|---|
| Relocation | `p23` → `p134` for this album (docs elsewhere say `p123`). The partition is per-album; always follow `X-Apple-MMe-Host`. It arrives in **both** the body and the headers. |
| Derivative keys | Keyed by the **long edge**, not height. A landscape photo appears under `"2731"` with `width: 2731, height: 1536`. Selecting on the largest reported `height` still picks correctly. |
| Value types | `width`, `height`, `fileSize` are **strings** (`"2731"`), not integers. Cast them. |
| Resolution | Long edge came back at **~2731px**, better than the ~2048 assumed. Do not treat 2048 as a hard cap. |
| `itemsReturned` | Reported `0` while `photos[]` held real entries. **Ignore it**; count `photos[]`. |
| Original filename | `url_path` contains the real camera filename (`/S/<token>/IMG_3850.JPG?o=...`). This is now used for `original_filename` — it is the best possible handle for matching a photo back to the master library. |

Two logging bugs found and fixed during that first run:

- The channel inherited `env('LOG_LEVEL')`, which is **`warning`** in production, so every routine sync summary was discarded and the log file was never even created. It now defaults to `info` independently.
- The test suite runs as `admin` and created the log file, after which the `www-data` queue worker could not write to it. Tests now write to `logs/testing/` via `ICLOUD_LOG_FILE` in `phpunit.xml`.

Both failure modes were silent. If sync ever appears to do nothing, check `storage/logs/icloud-sync-*.log` exists **and is owned by www-data**.

---

## 0. Preflight

1. Read `/var/www/portfolio/CLAUDE.md`. It is the project contract and overrides this document wherever they disagree.
2. Read the memory index at `~/.claude/projects/-var-www-portfolio/memory/MEMORY.md` (VPS path) and the files it links.
3. **Confirm the plan with Ugis before writing code** — `CLAUDE.md` requires checking in before any new feature, and a standing memory rule requires a plan table accepted explicitly first.
4. Re-verify §1 before trusting it. This was written 2026-07-21.

### Hard limits

| Limit | Meaning here |
|---|---|
| Never push to GitHub | Ugis branches and pushes himself. |
| Never modify DB data directly | This plan adds **2 migrations**. Explicit go-ahead required. |
| Never apply changes on live without asking | `/var/www/portfolio` is production. **No backups of any kind exist.** |
| Out of scope | E-commerce, payments, checkout, print ordering. |

Reporting to Ugis is **very terse** — one or two sentences. He is the sole reviewer, so flag risks and name what is unverified. Never imply something works when it has not been checked.

---

## 1. Ground truth (verified on the live box, 2026-07-21)

| Fact | Value |
|---|---|
| App | Laravel `^13.8`, PHP `^8.3`, `intervention/image ^4.1` (GD driver) |
| DB | **MySQL**, database `portfolio` |
| Queue | `QUEUE_CONNECTION=database`, one `queue:work` process running |
| Scheduler | Cron installed: `* * * * * cd /var/www/portfolio && php artisan schedule:run` |
| Storage | `FILESYSTEM_DISK=local`, disk root `storage_path('app/private')` |
| Mail | `MAIL_MAILER=log` — **does not send** |
| opcache | `validate_timestamps=On` — copied files take effect immediately |
| Theme | `version-2` is live and the **only theme in scope** |
| Host | `ssh admin@photos-ip` → `172.238.246.150` → loops back to the same machine |

### Disk budget — the binding constraint

| Measure | Value |
|---|---|
| Root filesystem | 25 GB total, **6.4 GB free (72% used)** |
| Photos in DB | 554 rows, **3.16 GB total**, avg 5.98 MB, max 15.2 MB |
| Live photo dir | `storage/app/private/photos`, owned `www-data` mode `0700` — not readable as `admin` |

Those 554 photos average ~6 MB, i.e. full-resolution uploads. `CLAUDE.md` states site uploads are **only for testing**.

Consequences:

- iCloud derivatives are ~0.5–1.5 MB each, so ~1 MB budgeted, plus ~10% for 600px thumbnails. **6.4 GB free ≈ 5,000–6,000 synced photos** before the disk fills — and that leaves no headroom for MySQL, logs, or the OS.
- **Recommend reclaiming the 3.16 GB of test uploads** once sync is trusted. That roughly doubles usable space and is the cheapest capacity win available. Requires Ugis's confirmation — deleting Photo rows through Eloquent removes the files via `Photo::deleting`.
- Add a disk-space guard to the sync service: abort with a clear error below a floor (e.g. 1 GB free) rather than filling the root filesystem and taking the site down.

### Existing domain model

```
Album   id, parent_id, name, slug, description, date_taken, location,
        cover_photo_id, sort_order          (2 levels max)
Photo   id, album_id, original_filename, original_path, thumbnail_path,
        filesize, width, height, captured_at, status, sort_order
        status ∈ pending | processing | ready | failed
Download / ContactMessage / ActivityLog / Setting
```

Behaviour to preserve:

- `GalleryController::show()` loads `status = 'ready'`, ordered `COALESCE(captured_at, created_at) ASC`, then `original_filename`.
- `Photo::deleting` removes `original_path` and `thumbnail_path` from disk. Always delete photos through Eloquent, never a mass `DELETE`.
- `Album::deleting` deletes child photos through Eloquent so the above fires.
- `PhotoUploadService::finalizeUpload()` assigns the album cover when unset **and** the parent album's cover when the album is a subalbum whose parent has none.
- Album slugs: `Str::slug($name) . '-' . Str::random(6)`.
- `GenerateThumbnailJob`: 600px wide, quality 80, reads EXIF `DateTimeOriginal`/`DateTime`, sets `status = ready`. `tries = 3`, `timeout = 120`.
- `PhotoUploadService` verifies uploads are genuinely JPEG via `getimagesize() === IMAGETYPE_JPEG`.

---

## 2. What changed in revision 2

Revision 1 assumed Ugis would create an album in the admin UI and then paste a share link into it. That contradicts `CLAUDE.md`: *"one-way; the iCloud folder is the source of truth."* If iCloud is authoritative, **the sync should create the album**, not adopt one.

Four changes:

1. **Albums are created by the sync** from `streamName`, via `php artisan icloud:link {token}`. No manual pre-creation, no admin UI needed to get running.
2. **Pruning is off by default.** With no backups, a diff bug that deletes the wrong side is unrecoverable. Sync *reports* what it would remove; deletion is opt-in per album once trusted.
3. **MySQL-specific index sizing** — the composite unique index needs a bounded column length.
4. **Disk guard** — see §1.

---

## 3. Architecture

### 3.1 Why Shared Albums

iCloud Photos has **no official API**. Options considered:

1. **Shared Albums** *(chosen)* — a published album yields a public link whose token backs an undocumented but long-stable JSON API. No Apple credentials stored, no 2FA to break, pure server-side HTTP. **Sharing is the selection mechanism**, so "selective folders" needs no picker UI.
2. **Account-level API** (pyicloud-style) — Apple ID password plus a session cookie expiring roughly every 2 months, and a Python sidecar beside PHP. Fragile and ToS-grey.
3. **Mac-side export** — Photos.app + Shortcut rsync. Full-res, but needs a Mac permanently on.

**Accepted tradeoff:** Apple downscales shared assets to ~2048px long edge. The site becomes a browsing catalogue rather than a master archive.

Two properties this buys, both valuable given `CLAUDE.md` records **no backups of anything**:

- **Photo data becomes re-derivable.** If the DB or disk is lost, re-running sync rebuilds every synced photo from iCloud. The irreplaceable data shrinks to *curation metadata* — album descriptions, dates, locations, cover choices, sort order — which is small and worth a periodic dump.
- **A server compromise cannot leak full-resolution originals**, because they are never on the server.

### 3.2 Ownership split

Sync owns photo rows and files. Ugis owns curation. **Sync must never overwrite a curated field.**

| Field | Owner | On re-sync |
|---|---|---|
| `Album.name` | Sync on create, Ugis after | Never overwritten |
| `Album.slug` | Sync on create | Never regenerated |
| `Album.description` / `date_taken` / `location` / `sort_order` | Ugis | Untouched |
| `Album.cover_photo_id` | Sync only if null | Untouched once set |
| `Photo.*` | Sync | Reconciled by `icloud_photo_guid` |

### 3.3 The protocol (undocumented — expect eventual breakage)

Share link `https://www.icloud.com/sharedalbum/#B0xxxxxxxxx` → **token** is everything after `#`.

**1. Fetch the stream**

```
POST https://p{NN}-sharedstreams.icloud.com/{token}/sharedstreams/webstream
Content-Type: application/json
{"streamCtag": null}
```

Start at partition `p23`. If the response carries `X-Apple-MMe-Host` (HTTP 330 relocation), rebuild the base URL with that host and retry **once**. Relocation is normal, not an error.

**2. Response**

```jsonc
{
  "streamName": "Iceland 2025",
  "streamCtag": "...",          // album version marker
  "itemsReturned": 128,
  "photos": [{
    "photoGuid": "...",         // stable identity → dedupe key
    "caption": "",              // usually empty
    "dateCreated": "2025-06-14T...",
    "batchDateCreated": "...",
    "derivatives": {            // keyed by height, as strings
      "2048": {"checksum": "...", "fileSize": 1234567, "width": 1536, "height": 2048},
      "342":  {"checksum": "...", ...}
    }
  }]
}
```

Pick the derivative with the **largest `height`**.

**3. Resolve asset URLs**

```
POST {base}/webasseturls
{"photoGuids": ["guid1", ...]}
→ {"items": {"<checksum>": {"url_location": "cvws.icloud-content.com", "url_path": "/B/..."}}}
```

Download URL = `https://` + `url_location` + `url_path`.

> **Signed URLs expire in roughly one hour.** Resolve them in batches of ~25 *immediately before* downloading that batch. Never resolve everything upfront then start downloading — a large album will fail partway through.

`streamCtag` unchanged ⇒ nothing changed ⇒ the run costs one request.

---

## 4. Migrations (explicit go-ahead required)

**`XXXX_add_icloud_sync_to_albums_table.php`**

```php
Schema::table('albums', function (Blueprint $table) {
    $table->string('icloud_token', 128)->nullable()->unique();
    $table->string('icloud_stream_ctag', 128)->nullable();
    $table->timestamp('icloud_last_synced_at')->nullable();
    $table->string('icloud_sync_status', 16)->nullable();   // idle|syncing|failed
    $table->text('icloud_sync_error')->nullable();
    $table->boolean('icloud_auto_sync')->default(true);
    $table->boolean('icloud_prune')->default(false);        // see §6.4
});
```

**`XXXX_add_icloud_fields_to_photos_table.php`**

```php
Schema::table('photos', function (Blueprint $table) {
    $table->string('icloud_photo_guid', 64)->nullable();
    $table->string('icloud_checksum', 128)->nullable();
    $table->unique(['album_id', 'icloud_photo_guid'], 'photos_album_icloud_guid_unique');
});
```

Two MySQL-specific points:

- **Bound the column lengths.** Default `string()` is `varchar(255)`; under `utf8mb4` a composite index on a 255-char column plus a `bigint` approaches InnoDB's key limit. iCloud GUIDs are ~36 chars, so 64 is generous.
- **Use `string`, not `enum`.** Altering a MySQL `ENUM` later is painful, and Laravel validates the values anyway.

The composite unique index is the single most important line here: it makes sync **idempotent**, so a re-run can never duplicate a photo no matter how a previous run died.

Add all new columns to `$fillable` on both models.

---

## 5. New files

| File | Responsibility |
|---|---|
| `app/Services/ICloud/SharedAlbumClient.php` | Protocol only. `fetchStream(string $token, ?string $knownCtag): ?array`, `fetchAssetUrls(string $token, string $base, array $guids): array`. Owns host relocation, timeouts, retries. Uses the `Http` facade so it fakes cleanly. **No DB access.** |
| `app/Services/ICloud/SharedAlbumSyncService.php` | Orchestration: diff, download, create, prune-report, update album state. |
| `app/Jobs/SyncICloudAlbumJob.php` | `implements ShouldQueue, ShouldBeUnique`; `uniqueId()` = album id; `$tries = 3`; `$timeout = 900`. |
| `app/Console/Commands/LinkICloudAlbum.php` | `php artisan icloud:link {token} {--parent=}` — validates the token, creates the Album from `streamName`, triggers first sync. |
| `app/Console/Commands/SyncICloudAlbums.php` | `php artisan icloud:sync {album?} {--force} {--dry-run}` |

### Client sketch

```php
final class SharedAlbumClient
{
    private const DEFAULT_PARTITION = 'p23';

    /** @return array{stream: array, base: string}|null  null = unchanged */
    public function fetchStream(string $token, ?string $knownCtag = null): ?array
    {
        $base = "https://" . self::DEFAULT_PARTITION . "-sharedstreams.icloud.com/{$token}/sharedstreams";
        $response = Http::timeout(30)->acceptJson()->post("{$base}/webstream", ['streamCtag' => null]);

        // Apple may relocate the album to another partition; follow once.
        if ($host = data_get($response->json(), 'X-Apple-MMe-Host')) {
            $base = "https://{$host}/{$token}/sharedstreams";
            $response = Http::timeout(30)->acceptJson()->post("{$base}/webstream", ['streamCtag' => null]);
        }

        $response->throw();
        $stream = $response->json();

        if ($knownCtag !== null && ($stream['streamCtag'] ?? null) === $knownCtag) {
            return null;
        }

        return ['stream' => $stream, 'base' => $base];
    }
}
```

### Sync service flow

1. Guard: abort if free disk is below the floor (§1).
2. `$album->update(['icloud_sync_status' => 'syncing'])`.
3. `fetchStream($token, $album->icloud_stream_ctag)` → `null` means unchanged: touch `icloud_last_synced_at`, set `idle`, return.
4. Key `$remote` by `photoGuid`; load `$local = $album->photos()->whereNotNull('icloud_photo_guid')->pluck('id', 'icloud_photo_guid')`.
5. **Create** = remote keys absent locally. **Prune candidates** = local keys absent remotely.
6. Per batch of 25 new guids: `fetchAssetUrls()`, then download each to `photos/originals/{uuid}.jpg` through `Storage::disk('local')`.
7. Verify the bytes are genuinely JPEG (`getimagesize() === IMAGETYPE_JPEG`) before creating the row, mirroring `PhotoUploadService`.
8. Create the `Photo` with `status = 'pending'`, `icloud_photo_guid`, `icloud_checksum`, `filesize`, and **`captured_at` from the stream's `dateCreated`** (§6.1).
9. Call the shared cover helper (§6.2).
10. `GenerateThumbnailJob::dispatch($photo)`.
11. Prune candidates: **report only** unless `icloud_prune` is true (§6.4). When enabled, delete through Eloquent so files are cleaned up.
12. On success store the new `streamCtag`, set `icloud_last_synced_at = now()`, status `idle`, clear `icloud_sync_error`.

Wrap per-photo work in `try/catch`. One bad asset must not fail the album — count failures and surface them.

### Scheduling

`routes/console.php` already uses the `Schedule` facade with a `->daily()->name(...)->withoutOverlapping()` chunk-purge task. Match that style:

```php
Schedule::command('icloud:sync')
    ->hourly()
    ->name('icloud-album-sync')
    ->withoutOverlapping();
```

---

## 6. Required changes to existing code

All four are verified issues, not speculation.

### 6.1 `captured_at` will be null for every synced photo

`GenerateThumbnailJob::readCapturedAt()` reads EXIF via `exif_read_data`. **iCloud strips most EXIF from its derivatives.** Left alone, every synced photo gets `captured_at = null`, breaking `GalleryController::show()`'s `COALESCE(captured_at, created_at)` ordering — albums would sort by download order rather than capture time.

Fix: the sync service writes `captured_at` from the stream's `dateCreated`, and `GenerateThumbnailJob` only sets it when still null:

```php
$update = [/* thumbnail_path, width, height, status */];
if ($this->photo->captured_at === null) {
    $update['captured_at'] = $this->readCapturedAt($originalFullPath);
}
$this->photo->update($update);
```

### 6.2 Cover assignment is duplicated logic

It lives inline in `PhotoUploadService::finalizeUpload()`, including the non-obvious rule that a subalbum's first photo also becomes the *parent's* cover. Extract to one shared method — e.g. `Album::assignCoverIfMissing(Photo $photo): void` — and call it from both paths. Do not copy-paste. This is the only real refactor here.

### 6.3 `ActivityLog::log()` silently no-ops from the queue

It returns early when `Auth::check()` is false. Sync runs from cron and the queue worker, where nobody is authenticated, so **every sync log line would vanish**. Either log via `Log::info()` on a dedicated channel, or extend `ActivityLog::log()` to record system actions with `user_id = null`. Pick one; do not assume `ActivityLog` works from a job.

### 6.4 Pruning is destructive and there are no backups

If the diff is inverted or the remote returns a partial list, prune-by-default deletes a live gallery with no way back. Therefore:

- `icloud_prune` defaults to **false**.
- With it false, sync logs `would prune N photos` and changes nothing.
- `--dry-run` prints the full plan without writing.
- Enable per album only after observing correct reports across several runs.

Additional safety: refuse to prune if the remote returned **zero** photos while local has many — that pattern means a failed or empty response, not a genuinely emptied album.

---

## 7. Admin UI — scope decision required

A standing memory rule (2026-07-18) says *"never edit anything in the admin dashboard"*, but it was scoped to landing-page chats. `CLAUDE.md` says admin polish is *lower priority*, not forbidden. **These conflict — ask Ugis.**

Sync is fully usable without any admin UI:

```bash
php artisan icloud:link B0xxxxxxxxx      # creates the album, first sync
php artisan icloud:sync                  # all auto-sync albums
php artisan icloud:sync 3 --dry-run      # one album, no writes
```

If approved later: a sync-status column and a **Sync now** button on the albums index, plus an "iCloud Shared Album link" field on album edit.

---

## 8. Failure modes

| Failure | Handling |
|---|---|
| Token revoked / album unshared (404) | Status `failed`, record error, **keep existing photos**. A revoked link must never wipe a gallery. |
| Single asset 404s or times out | Per-photo `try/catch`, continue, report failure count. |
| Signed URL expired mid-run | Prevented by batch-then-download ordering (§3.3). |
| Remote returns zero photos | Never prune (§6.4). |
| Disk full | Pre-flight guard aborts with a clear error (§1). |
| Two syncs overlap | `ShouldBeUnique` + `withoutOverlapping()`. |
| Apple changes the API | Undocumented; expect eventual breakage. Failures must be loud, never silent. |
| Album exceeds Apple limits | 5000 photos per shared album, 200 shared albums per account. |

---

## 9. Sequencing

| Phase | Deliverable |
|---|---|
| 0 | Plan accepted; migrations approved; disk-reclaim decision (§1) |
| 1 | `SharedAlbumClient` + unit tests against a recorded fixture |
| 2 | Migrations; `SharedAlbumSyncService`; `icloud:link` / `icloud:sync`; §6.1–6.3 fixes. Verify end-to-end from CLI against one real album. |
| 3 | `SyncICloudAlbumJob` + schedule entry + failure visibility |
| 4 | Enable pruning per album once reports look right (§6.4) |

Phase 2 is the substance — the site works from the CLI at the end of it.

---

## 10. Testing

Existing suite: `phpunit.xml`, `tests/Feature/Admin/AlbumManagementTest.php`, `tests/Feature/Auth/*`, `tests/Feature/ProfileTest.php`. **Ugis believes there are no tests and does not use them** — run `./artisan test` first to learn the real state. Do not claim tests are absent, and do not assume they are green.

With `Http::fake()` and fixture JSON captured from a real shared album:

- first sync creates N photos with correct `icloud_photo_guid` and `captured_at`
- re-sync with unchanged `streamCtag` issues one request and creates nothing
- re-sync after a remote addition creates exactly one photo
- a photo removed upstream is **reported, not deleted** while `icloud_prune` is false
- with `icloud_prune` true it is deleted and its files removed from disk
- a remote returning zero photos never prunes
- a revoked token sets status `failed` without touching existing photos
- a mid-batch asset failure still commits the photos that succeeded
- `icloud:link` creates an album whose `name` matches `streamName`, and a second run does not duplicate it

---

## 11. Deployment

**No backups of anything exist.** `/var/www/portfolio` is production.

1. Ask before applying anything to the live server.
2. Back up each file before overwriting — `.backups/` in the repo root is in use for this.
3. Migrations need separate explicit go-ahead. Take a `mysqldump` of `portfolio` first regardless.
4. `opcache.validate_timestamps=On`, so copied PHP takes effect immediately, no restart.
5. **Run `php artisan queue:restart` after deploying job classes** — the running worker holds stale code in memory.
6. Do not push to GitHub.
7. UI-affecting changes: verify in a browser or state plainly that they are unverified.

---

## 12. Full-resolution requests (companion feature)

Planned separately and **independently shippable** — it works against already-uploaded photos with or without sync. Summary only; expand before building.

Visitors tick photos, leave an email, and Ugis fulfils manually from his own library. This is what makes the ~2048px cap acceptable: the server holds a catalogue, originals never touch it. **Not e-commerce** — no pricing, no payment. Keep the wording "request", never "order".

Two hard parts:

- **The join key.** Fulfilment needs Ugis to find the original in his library. iCloud does not reliably preserve original filenames, so the fulfilment screen must show thumbnail + `captured_at` + filename + `icloud_photo_guid`. `captured_at` is the practical locator — which is why §6.1 is load-bearing for this feature too.
- **Unrendered photos have no checkbox.** `albums/show.blade.php` renders 30 at a time; photos not scrolled past have no DOM checkbox and cannot be submitted. Selection state must move into a JS `Set` keyed by photo id, serialized to hidden inputs on submit. This also enables a real "Select all" and improves the existing download flow.

**Blocker:** `MAIL_MAILER=log` — notifications go nowhere. Either configure a real mailer, or ship v1 as an admin inbox Ugis checks manually (legitimate). Do not build a mailable that quietly writes to a log and call it done.
