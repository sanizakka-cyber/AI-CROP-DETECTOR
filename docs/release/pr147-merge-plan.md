# PR #147 merge plan: backup, rollback and smoke test

PR #147 (`phase12-scan-parity` → `main`) contains Phase 12.1 and the Phase 12.2 fixes. The written approval says to merge once tasks 1 and 2 pass CI **and** the database backup is taken.

## What the merge changes in production

- **Laravel `msas-farmai`**:
  - **Three migrations** run on deploy:
    - `2026_10_16_000003_add_is_qa_to_users`: a new nullable column.
    - `2026_10_18_000001_add_ai_consensus_to_diagnoses`: a new nullable JSON column.
    - `2026_10_18_000002_recompute_diagnosis_severity`: rewrites `diagnoses.severity_level` from each scan's own AI reply. Every changed row's old value is kept in `diagnosis_severity_backfill`, and `down()` restores them.
  - **New behaviour**:
    - One severity value everywhere.
    - "Likely X, please confirm" from 65% up to (not including) 75%.
    - The CEO account is no longer created from a password in the repo.
    - Live Paystack keys are refused outside production. Production is unaffected.
- **AI engine `msas-ai-engine`**: each scan now makes **3 Claude calls in parallel** (`SCAN_PASSES`, default 3).
  - **Cost:** about 3 times the AI cost per scan.
  - **Speed:** wall time stays close to one call.
  - **To return to one call without a deploy:** Render → `msas-ai-engine` → Environment → `SCAN_PASSES=1`.
- **Android app**: no new APK is needed. Build 24 keeps working against the new API:
  - Its header badge now shows the real severity, because the API field it reads is fixed.
  - It does not show the "Likely" wording: that needs the next build.
  - Its emergency colour no longer triggers on the urgency. That also needs the next build.

## 1. Before merging

1. **CI green** on the PR head: AI Engine, Mobile, PostgreSQL, Security Regression and Secret Scan.
2. **Database backup.** Take one of these, and keep the file and its SHA-256:
   - **Render backup (paid database plans).** Dashboard → `msas-db` → **Recovery** (or **Backups**) → **Create export**. When it finishes, **Download**.
   - **pg_dump from your PC (any plan).** Needs PostgreSQL client tools of the server's major version or newer: the EDB "zip archive" from enterprisedb.com/download-postgresql-binaries, unzipped, with `bin` used directly. Render → `msas-db` → **Connect** → **External Connection** → copy the URL. Then:

     ```powershell
     $env:PROD_DATABASE_URL = (Get-Clipboard)          # never typed, never pasted into chat
     $stamp = Get-Date -Format yyyyMMdd-HHmm
     & "C:\pgsql\bin\pg_dump.exe" --format=custom --no-owner --no-acl --file "msas-prod-$stamp.dump" $env:PROD_DATABASE_URL
     & "C:\pgsql\bin\pg_restore.exe" --list "msas-prod-$stamp.dump" | Select-Object -First 5   # proves the file is readable
     Get-FileHash "msas-prod-$stamp.dump" -Algorithm SHA256
     Remove-Item Env:PROD_DATABASE_URL
     ```
   - Do **not** rely on the nightly `db:backup`. It writes to the container's temporary disk, and its email goes to `MAIL_FROM_ADDRESS` (`noreply@msasagro.com`), so it reaches nobody. That is a separate fix.
3. **Note the current deploys:** Render → `msas-farmai` → **Events**, and the same for `msas-ai-engine`. Write down the commit of the live deploy of each. That is what a rollback returns to.

## 2. Merge

Merge #147 on GitHub with **Create a merge commit**, which keeps the history readable for a rollback. Render deploys `main` automatically. Watch both services reach **Live**: Laravel runs the migrations in `docker/start.sh` before it serves.

## 3. Smoke test (right after the deploy)

Run `scripts/live/smoke_test.py` against production. It needs:
- an account with at least 4 scans left this month;
- one photo per scan type;
- `SMOKE_URL`, `SMOKE_EMAIL` and `SMOKE_PASSWORD` set from your password manager, never typed into a command.

```powershell
$env:SMOKE_URL = "https://www.msasagro.com"
$env:SMOKE_EMAIL = "<your test account>"
$env:SMOKE_PASSWORD = (Get-Clipboard)
py -I scripts\live\smoke_test.py --production --photos crop=leaf.jpg livestock=cow.jpg soil=soil.jpg pest=larva.jpg --out smoke-prod.json
```

It checks:
- the health endpoint;
- app login;
- **all 4 scan types**: each returns an outcome, and the response shows 3 completed passes;
- the **severity rule** (severity equals the severity level, never the urgency);
- the **low-confidence rule** (Not certain below 65, Likely from 65 up to 75);
- **history** lists the new scans;
- web login, the **history page** with its disclaimer, and the **PDF report** page;
- the **subscription plans** page.

By hand, in the app (build 24) and on the web:
- Open one of the new scans. The header severity and the details severity must match.
- Open an older low-confidence scan, or scan a blurred photo. It must show "Not certain" with the possible conditions.
- **Payments.** On production, start a subscription checkout, check Paystack's page opens with the right amount, and **cancel** there. A full test payment belongs on staging with test keys. Production must never take a test-mode payment.

## 4. Rollback (if the smoke test fails or errors rise)

The order matters. Undo the migrations **while the new code is still live**: older code does not contain these migration files, so it cannot reverse them.

1. **AI engine only.** If the problem is cost or speed, set `SCAN_PASSES=1` on `msas-ai-engine` and save. No code rollback is needed.
2. **Database, while #147's code is still live.** Render → `msas-farmai` → **Shell**:
   ```bash
   php artisan migrate:status | tail -5        # the three 2026_10_1x migrations are in the last batch
   php artisan migrate:rollback --force        # reverses that batch: severity values restored, new columns dropped
   ```
3. **Code, immediately after.** Render → `msas-farmai` → **Events** → the deploy noted in step 1.3 → **Rollback**. Do the same for `msas-ai-engine`. If the services restart before this, `start.sh` re-applies the migrations; then simply repeat step 2.
4. **If the data itself is damaged:** restore the backup from step 1.2 into a **new** Render database first (never over the live one), check it, then point `msas-farmai` at it. Restoring the database is the owner's decision.
5. Revert the merge on GitHub ("Revert" on the PR), so `main` matches what is running.

## 5. The 24 hours after the deploy

- **Health:** `/api/health` polled every 10 minutes from this PC, with results logged.
- **Errors (owner):** Render → `msas-farmai` → **Logs**, filtered by `ERROR` and `CRITICAL`; and `msas-ai-engine` → **Logs**, filtered by `passes failed` and `provider_unavailable`.
- **Report:** smoke-test results, health uptime, and the counts of errors and of "N of 3 passes failed" lines.
