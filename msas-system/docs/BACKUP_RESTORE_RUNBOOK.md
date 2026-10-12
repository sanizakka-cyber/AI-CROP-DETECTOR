# Backup & Restore Runbook

Phase 9 §B2. This is a runbook for a human to execute, not an automated
process — restoring a backup into production is a deliberate, rare,
high-consequence action that should never happen without a person
making that specific call at that specific moment.

## Context

- Backups are produced by `php artisan db:backup --email` (`app/Console/Commands/BackupDatabase.php`), scheduled daily at 02:00 UTC (`routes/console.php`).
- As of the Phase 9 fix, each backup is gzipped and **emailed as an attachment** to `MAIL_FROM_ADDRESS` (the CEO), not just referenced by a path on the container's disk.
- **Why email and not disk:** Render's web service disk is ephemeral — anything written only to `storage/backups/` is gone on the next deploy or restart. The email attachment is what actually gets the backup off the container.
- **Known ceiling:** attachments over 20MB are **not sent** — the command instead emails a warning naming the compressed size and the fact that the backup "has NOT left the server." If you ever receive that warning, retrieve the file from the container's disk *before* the next deploy, because that's the only copy that exists.

## Recommendation: move off email entirely

Email was the pragmatic fix within this session's reach (uses existing SMTP config, zero new infrastructure, zero new cost). It is not where backups should live long-term:

- No retention/versioning beyond whatever the CEO's mailbox keeps.
- The 20MB ceiling will eventually bind as the database grows.
- A single mailbox is a single point of failure for the only durable copy.

**Recommended instead:** off-site object storage — Render's own [Render Disks](https://render.com/docs/disks) won't help here (same ephemeral-on-redeploy problem for a *web* service), but S3, Cloudflare R2, or even a Google Drive service-account upload would all solve the durability problem properly. This needs the owner's choice of provider and a new set of credentials — out of scope to pick unilaterally here.

## Restore drill procedure

Run this periodically (recommended: monthly, and immediately after the scheduler fix is first confirmed live) to prove backups are actually restorable, not just present.

1. **Get a recent backup file.** Either the `.sql.gz` attachment from the daily backup email, or (if backups are migrated to object storage per the recommendation above) downloaded from there.
2. **Set Postgres connection env vars** to the *same server* the backup came from (a scratch database is created there, not a new server):
   ```bash
   export PGHOST=<render postgres host>
   export PGPORT=<render postgres port>
   export PGUSER=<render postgres user>
   export PGPASSWORD=<render postgres password>
   ```
3. **Run the drill script:**
   ```bash
   ./scripts/restore-backup.sh /path/to/msas_backup_YYYY-MM-DD_HH-MM-SS.sql.gz
   ```
   This creates a disposable `msas_restore_drill_<timestamp>` database, restores into it, and prints row counts for `users`, `orders`, `payments`, `diagnoses`, and `subscriptions`. It never touches the production database by name.
4. **Compare the counts** against what you'd expect for that backup's date (e.g. against the CEO dashboard's own user/order counts from around that time). A restore that "succeeds" but produces obviously wrong counts (zero rows, or far fewer than expected) means the backup itself was bad, not that the restore step failed — this is exactly the failure mode a backup pipeline needs to be caught by a drill rather than discovered during a real incident.
5. **Promoting to production (only if an actual incident requires it):** this is intentionally *not* scripted. It means taking the application offline, taking a fresh backup of the current (presumably broken) production state first in case the restore needs to be reverted, then restoring the chosen backup directly into the production database connection. Get explicit sign-off before doing this — it is irreversible the moment it completes.
6. **Clean up:** `dropdb msas_restore_drill_<timestamp>` when done with the drill.

## Owner decisions this runbook surfaces

- Choose an off-site storage provider (S3 / R2 / Drive) and provide credentials, or explicitly accept email-as-durable-storage with its 20MB ceiling.
- Set a retention policy (the backup *file* rotation already keeps the last 7 locally per `BackupDatabase.php`, but that's on ephemeral disk — the email trail has no enforced retention at all).
- Decide and calendar a recurring restore-drill cadence; this document is the procedure, not the schedule.
