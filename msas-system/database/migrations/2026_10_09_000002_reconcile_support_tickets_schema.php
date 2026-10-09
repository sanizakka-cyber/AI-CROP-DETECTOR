<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7, Section 3/9/10 fix for a real, previously undiscovered defect:
 * two migrations both target 'support_tickets', each individually guarded
 * by `if (!Schema::hasTable('support_tickets'))`:
 *
 *   2026_07_06_000001_create_support_operations_extension_tables.php
 *     (earlier timestamp, runs first and wins) creates: id, user_id,
 *     assigned_to, subject, category, priority (ENUM low/medium/high/
 *     urgent), status (ENUM open/in_progress/resolved/closed), a NOT
 *     NULL description, reference, resolved_at, timestamps. No
 *     ticket_number, message, or resolution column.
 *
 *   2026_07_28_700001_create_support_tickets_table.php (later) defines
 *     ticket_number/message/category/priority (plain string)/status
 *     (plain string)/assigned_to/resolution/resolved_at -- but its own
 *     Schema::create is a no-op on every environment, because the table
 *     already exists from the July 6 migration.
 *
 * App\Models\SupportTicket and the real, currently-routed farmer-facing
 * SupportTicketController::store() assume the JULY 28 shape: they write
 * ticket_number (via SupportTicket::generateNumber()) and message, and
 * SupportTicketController::store()'s own validation accepts
 * priority=normal -- a value the July 6 ENUM does not. The result,
 * confirmed by SupportTicketSchemaTest (added alongside this fix): every
 * real submission through this route fails -- either on a missing
 * ticket_number column, a NOT NULL violation on the unpopulated
 * description column, or an invalid-enum-value error on priority, on
 * whichever check Postgres reaches first. No test existed for this
 * controller before this phase, which is why it was never caught.
 *
 * This migration reconciles the live table to the shape the application
 * code actually uses, without dropping and recreating it (preserving
 * any real historical rows): adds the three missing columns, relaxes
 * description/reference to nullable (nothing in the current codebase
 * populates them, and they were never meant to coexist with message),
 * converts priority/status from native Postgres ENUM types to plain
 * VARCHAR so the validated value set lives in the application layer
 * (SupportTicketController's own validation rules) instead of being
 * silently out of sync with a DB-level constraint, and backfills a
 * generated ticket_number for any pre-existing rows so the later unique
 * index has no collisions.
 *
 * All of this is a no-op on an environment where July 28's migration
 * happened to create the table in its own correct shape already (the
 * hasColumn guards make every step idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('support_tickets')) {
            return;
        }

        if (!Schema::hasColumn('support_tickets', 'ticket_number')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->string('ticket_number')->nullable()->after('user_id');
            });
        }
        if (!Schema::hasColumn('support_tickets', 'message')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->text('message')->nullable()->after('subject');
            });
        }
        if (!Schema::hasColumn('support_tickets', 'resolution')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->text('resolution')->nullable()->after('status');
            });
        }

        // Backfill any pre-existing rows (e.g. ones created through some
        // other path before this fix) so the unique index below never
        // collides on an empty string. Postgres allows multiple NULLs
        // under a plain unique index, so rows that stay NULL are fine too.
        DB::table('support_tickets')->whereNull('ticket_number')->orderBy('id')
            ->each(function ($row) {
                DB::table('support_tickets')->where('id', $row->id)->update([
                    'ticket_number' => 'TKT-' . strtoupper(substr(md5('backfill-' . $row->id), 0, 8)),
                ]);
            });

        // Postgres-only from here: raw ALTER COLUMN (type conversion) has
        // no SQLite equivalent at all -- production is always pgsql
        // (render.yaml); SQLite is test-only. The description NOT NULL
        // constraint itself is handled portably instead, by having
        // SupportTicketController::store() populate the column directly
        // (see that controller) rather than depending on a database-
        // specific ALTER to relax it -- this DROP NOT NULL is additional
        // defense-in-depth for Postgres specifically, not the primary fix.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE support_tickets ALTER COLUMN description DROP NOT NULL');

            // Correction while verifying this migration against a real
            // Postgres CI run: Laravel's $table->enum() on Postgres does
            // NOT create a native USER-DEFINED enum type (that's a MySQL
            // behavior) -- it creates a plain VARCHAR with a CHECK
            // constraint (named "{table}_{column}_check" by default),
            // restricted to the original value set, which doesn't include
            // every value the real controller validates and writes (e.g.
            // priority=normal). Confirmed live: inserting priority=normal
            // failed with "violates check constraint
            // support_tickets_priority_check". Dropping the constraint
            // moves validation to the application layer (already enforced
            // by SupportTicketController's own 'required|in:...' rules)
            // instead of leaving it silently split across two different,
            // driftable value sets. Looked up by querying the catalog
            // rather than assuming Postgres's default naming convention,
            // in case either column's constraint was ever given an
            // explicit name.
            $checks = DB::select(<<<'SQL'
                SELECT con.conname
                FROM pg_constraint con
                JOIN pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'support_tickets'
                  AND con.contype = 'c'
                  AND pg_get_constraintdef(con.oid) ~ '\((priority|status)\)'
            SQL);
            foreach ($checks as $check) {
                DB::statement('ALTER TABLE support_tickets DROP CONSTRAINT "' . $check->conname . '"');
            }
        }

        // The Schema facade has no hasIndex() helper, so guard the unique
        // constraint the same way other migrations in this codebase guard
        // an index: attempt it and swallow an "already exists" error
        // (e.g. on an environment where July 28's migration actually
        // created the table in its own correct shape already).
        try {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->unique('ticket_number');
            });
        } catch (\Throwable $e) {
            // Already unique -- fine.
        }
    }

    public function down(): void
    {
        // Deliberately not reversible -- this migration only relaxes
        // constraints and backfills data to match code that already
        // depends on this shape; reversing it would re-break the feature
        // it fixes. down() documents that choice rather than guessing.
    }
};
