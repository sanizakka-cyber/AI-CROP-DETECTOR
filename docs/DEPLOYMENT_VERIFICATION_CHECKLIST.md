# Deployment Verification Checklist — This Session's Changes

Covers everything merged to `main` in this audit (commits `b1977a3`..`b962196`):
support ticket schema fix, subscription enum fix, document-viewing +
per-document verification lifecycle, payment/security fixes, consultation
accept/decline routing, and mobile geolocation fixes. CI (GitHub Actions)
has run every commit in this range against both a fresh SQLite database and
a real `postgres:16` container — that is evidence the code is internally
consistent and the migrations run cleanly from scratch. **It is not evidence
that the deployed production application works** — production is a
different, already-populated database that these CI runs never touch.

## Before deploying

- [ ] Confirm `main` is what will actually be deployed (no uncommitted local
      changes, no divergent branch) — `git status`, `git log origin/main..main`.
- [ ] Take a manual Postgres backup (`docs/RENDER_POSTGRES_BACKUP_RUNBOOK.md`
      §3) before this deploy, since `msas-db` has no automated backups today
      (free Render plan) and this deploy includes a real schema migration
      (`2026_10_11_000001_add_verification_lifecycle_to_user_documents.php`).
- [ ] Confirm `msas-db`'s free-tier 90-day trial has not already expired
      (Render dashboard) — if it has, resolve that first; a migration run
      against an expired/inaccessible database is not something to debug
      mid-deploy.

## New migrations in this range — read before deploying, not after

| Migration | What it does | Risk on existing data |
|---|---|---|
| `2026_10_09_000002_reconcile_support_tickets_schema.php` | Adds missing columns to `support_tickets`, drops two stale CHECK constraints (`support_tickets_priority_check`, `_status_check`) | Additive + constraint removal only; safe |
| `2026_06_19_000001_create_subscriptions_table.php` (edited) | Changes `plan` column from `enum()` to `string(30)` | **No-op on already-migrated production** — Laravel never re-runs a completed migration. Affects only fresh installs. |
| `2026_07_06_000001_create_support_operations_extension_tables.php` (edited) | Same enum→string change, for `support_tickets.priority`/`status` | Same: no-op in production, fresh-install-only |
| `2026_10_11_000001_add_verification_lifecycle_to_user_documents.php` | Adds `status`, `reviewed_at`, `reviewed_by`, `review_reason`, `replaces_document_id` to `user_documents` | Additive, all nullable/defaulted. Every existing document row gets `status='pending_review'` by column default — this is the first time this migration will actually run against production's real data, since it's new this session. |

**The one migration that matters for this specific deploy** is the last
one — it's the only one that hasn't already been exercised against a
database with real rows. Confirm after deploy (see below) that existing
`user_documents` rows read back with `status = 'pending_review'`, not null
or an error.

## After deploying

- [ ] Check Render's deploy log for the `msas-farmai` service: migrations
      should report success before PHP-FPM/Nginx start (per
      `docker/start.sh`). A migration failure here means Render kept the
      previous container serving traffic (health-check-gated deploy) —
      confirm you're actually looking at the NEW deploy's logs, not the
      still-running old one.
- [ ] Hit `/api/health` directly and confirm it responds.
- [ ] Log in to `/admin/applications` as an authorized admin and open any
      existing pending application with uploaded documents — confirm the
      document list renders with a status badge (not an error), confirming
      the new `user_documents` columns are actually present and readable.
- [ ] **The original reported defect**: find the specific Equipment Dealer
      application that prompted this entire audit, click **View** on its
      document. This is the one thing in this whole deployment that **only
      an authorized administrator with real production access can confirm**
      — I have not done this, could not do this (no production credentials
      in this environment, and I was explicitly told not to use any found
      in source), and no CI result substitutes for it. If it still fails,
      the exact error message now shown (e.g. "not stored correctly and
      cannot be displayed") tells you precisely which of the two fixed
      failure modes it is, rather than a blank page.
- [ ] Reject one test document via the new per-document UI and confirm the
      resubmission email actually arrives (SMTP works in production, unlike
      the CI environment where `MAIL_MAILER=array` never sends anything
      real) — this is also unverified by CI by design.
- [ ] Confirm `api_token` is actually nulled after a real password change
      (log in on a second device/token, change password on the first,
      confirm the second device's token now gets a 401).

## Known-unverifiable-by-me items

Everything in this checklist's "After deploying" section requires
production access I don't have in this environment. CI green is real
evidence the code is correct in isolation; it is not production
verification, and I'm not claiming it is.
