# Render / PostgreSQL Backup & Rollback Runbook

Written for the actual deployed architecture (`render.yaml`): a Render Docker web
service (`msas-farmai`, Laravel) and a Render-managed PostgreSQL database
(`msas-db`), not the Ubuntu/MySQL/VPS setup described in
`docs/DEPLOYMENT_GUIDE.md` (that document predates this architecture and does
not apply to it — see the note added at its top).

## 1. Current backup risk — read this first

`render.yaml:153` provisions `msas-db` on `plan: free`, with the file's own
comment: *"Free 90-day trial → upgrade to Starter ($7/mo) after"*.

**Render's free PostgreSQL plan does not include automated backups or
point-in-time recovery at all**, and the free trial itself expires 90 days
after creation. Two distinct risks follow from this, and I cannot resolve
either myself — both require your action in the Render dashboard:

- **No automated backup exists today.** If the database is lost, corrupted, or
  a bad migration damages data, there is nothing to restore from except a
  manual export you took yourself (see §3 below).
- **The free trial has an expiration.** If it has already elapsed, or elapses
  before you upgrade, confirm with Render directly what happens to the
  database and its data — don't assume it persists. Upgrading to the Starter
  plan (or higher) both removes the 90-day limit and enables Render's
  automated daily backups with point-in-time recovery.

**Action needed from you:** log into the Render dashboard, check `msas-db`'s
current plan and trial status, and upgrade it if backups matter to you (they
should, for a production database with real user data). This is a billing
decision only you can make.

## 2. How migrations actually run in this deployment

`msas-system/docker/start.sh`:
- Line 2: `set -e` — the script aborts immediately on the first failing command.
- Line 26: `php artisan migrate --force` — runs **before** PHP-FPM/Nginx start
  (lines 58-65), so a failing migration prevents the container from ever
  serving traffic.
- Render's deploy process only cuts traffic over to a new container once its
  health check (`healthCheckPath: /api/health`) passes. If migration fails,
  the new container never reaches that point, and **Render keeps the
  previous, already-running container serving traffic** — this is rollback
  of traffic, happening automatically, but it is not rollback of schema: if a
  migration partially applied before failing, that partial change is still
  sitting in the database, visible to the old container too.
- Laravel wraps each migration's `up()` method in a database transaction by
  default when the connection's grammar supports transactional DDL — true for
  PostgreSQL. In practice this means a migration that fails partway through
  (e.g. one `Schema::table()` call succeeds, a later one in the same file
  fails) rolls back everything in that file, not just the failing statement.
  This is the framework's own default behavior, not something configured in
  this codebase, and every migration file in `database/migrations/` relies
  on it rather than opting out.

**What this means in practice:** a bad migration blocks the deploy and keeps
the old version running — it does not corrupt the live schema under normal
circumstances. The actual risk is a migration that *succeeds* but does the
wrong thing to real data (e.g. drops a column still in use) — Laravel's
transaction wrapping doesn't protect against that, only against a failure
mid-migration.

## 3. Manual backup — do this before any risky migration

Until automated backups are confirmed (§1), take a manual export before any
schema change that touches existing data (not simple additive/nullable
columns, which this codebase already favors — see `database/migrations/`'s
consistent `hasColumn`/`hasTable` guards).

```bash
# From a machine with network access to Render (or via Render's own shell):
# Get connection details from the Render dashboard → msas-db → Connect,
# or from the DB_* environment variables Render injects into msas-farmai.

pg_dump --no-owner --no-privileges \
  "postgresql://<user>:<password>@<host>:<port>/<database>" \
  > msas-backup-$(date +%Y%m%d-%H%M%S).sql
```

Store the resulting file somewhere other than this repository (it contains
real user data) — encrypted cloud storage or a local secure location, not
git. Delete old backups you no longer need; don't accumulate them indefinitely
in an unmanaged location.

## 4. Restoring from a backup

```bash
psql "postgresql://<user>:<password>@<host>:<port>/<database>" \
  < msas-backup-20261011-120000.sql
```

Restoring into the *same* database will fail on existing tables/constraints
unless it's empty first — for a real incident, restore into a **new** Render
Postgres instance, verify the data, then repoint `msas-farmai`'s `DB_*`
environment variables at it (Render dashboard → msas-farmai → Environment),
rather than dropping the live database first.

## 5. If a deployed migration needs to be undone

There is no automatic schema rollback in this architecture (§2). To reverse a
migration that already ran in production:

1. Write a new, forward-only migration that undoes the specific change
   (matching this codebase's own convention — see
   `2026_10_09_000002_reconcile_support_tickets_schema.php` for an example of
   a later migration correcting an earlier one's mistake, rather than editing
   the original file). Never edit or delete a migration file that has already
   run in production — Laravel tracks completed migrations by filename in
   the `migrations` table, and editing one after the fact does not undo what
   it already did.
2. If data loss already occurred, restore from the most recent manual backup
   (§3/§4) first, then apply the fix migration.
3. Deploy normally — the existing `start.sh` migrate-before-serve behavior
   (§2) applies to this fix migration exactly as it does to any other.

## 6. Scope note

This runbook does not cover the AI engine (`msas-ai-engine`) or mobile app
release process — those have no database of their own to back up. It also
does not cover `server/`, a legacy Node.js stack with no entry in
`render.yaml` and no evidence of being deployed; it is out of scope for
production database procedures.
