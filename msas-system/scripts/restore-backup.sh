#!/bin/sh
# Phase 9 §B2 — restore a MSAS FarmAI database backup into a SCRATCH
# database and print sanity row counts. This script never touches the
# live production database by name — it always creates and restores
# into a separate, disposable database, so running it carries no risk
# to production. Promoting a verified backup into production is a
# separate, deliberate step the owner takes manually (see §B2 Step 5
# below) and is never automated here.
#
# Usage:
#   ./restore-backup.sh /path/to/msas_backup_YYYY-MM-DD_HH-MM-SS.sql.gz
#
# Requires: psql, createdb, dropdb, gunzip (or zcat) on PATH, and
# PGHOST/PGPORT/PGUSER/PGPASSWORD set to the same Postgres SERVER the
# backup came from (a scratch database is created on that same server
# — this script does not need superuser access beyond CREATEDB).

set -eu

BACKUP_FILE="${1:?Usage: $0 /path/to/msas_backup_*.sql.gz}"
SCRATCH_DB="msas_restore_drill_$(date +%Y%m%d_%H%M%S)"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE" >&2
    exit 1
fi

echo "==> Creating scratch database: $SCRATCH_DB"
createdb "$SCRATCH_DB"

echo "==> Restoring $BACKUP_FILE into $SCRATCH_DB"
case "$BACKUP_FILE" in
    *.gz) gunzip -c "$BACKUP_FILE" | psql -q -d "$SCRATCH_DB" ;;
    *)    psql -q -d "$SCRATCH_DB" -f "$BACKUP_FILE" ;;
esac

echo "==> Sanity row counts (scratch database, not production):"
psql -d "$SCRATCH_DB" -c "
    SELECT 'users' AS table_name, COUNT(*) FROM users
    UNION ALL SELECT 'orders', COUNT(*) FROM orders
    UNION ALL SELECT 'payments', COUNT(*) FROM payments
    UNION ALL SELECT 'diagnoses', COUNT(*) FROM diagnoses
    UNION ALL SELECT 'subscriptions', COUNT(*) FROM subscriptions
    ORDER BY table_name;
"

echo ""
echo "==> Restore drill complete. The scratch database '$SCRATCH_DB' is still on the server."
echo "    Review the counts above against what you expect for the backup's date, then:"
echo "      - To discard this drill:        dropdb $SCRATCH_DB"
echo "      - To promote to production:     see docs/BACKUP_RESTORE_RUNBOOK.md Step 5"
echo "        (a deliberate, manual, owner-approved step — never automated)."
