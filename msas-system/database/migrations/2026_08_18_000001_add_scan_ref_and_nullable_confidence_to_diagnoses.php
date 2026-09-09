<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('diagnoses', 'scan_ref')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->string('scan_ref')->nullable()->unique()->after('id');
            });
        }

        // Postgres-only from here down (raw ALTER COLUMN, sequences) —
        // production runs pgsql exclusively, but a SQLite test database
        // (phpunit.xml) has neither ALTER COLUMN nor CREATE SEQUENCE, and
        // doesn't need real atomic scan_ref generation to run feature tests.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // AI-unavailable scans must record "no score", not a fabricated 0.
        DB::statement('ALTER TABLE diagnoses ALTER COLUMN confidence_score DROP NOT NULL');

        // Atomic, concurrency-safe counter for the human-readable Scan ID.
        // Never reset — a global running sequence, so two simultaneous scans
        // can never collide (unlike a count()+1 read-then-write race).
        DB::statement('CREATE SEQUENCE IF NOT EXISTS diagnoses_scan_seq START 1');

        // Backfill existing rows in creation order so historical Scan IDs stay coherent.
        DB::table('diagnoses')->select('id', 'created_at')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $seq = DB::select("SELECT nextval('diagnoses_scan_seq') AS n")[0]->n;
                $date = \Illuminate\Support\Carbon::parse($row->created_at)->format('Ymd');
                $ref = sprintf('MSAS-SCN-%s-%06d', $date, $seq);
                DB::table('diagnoses')->where('id', $row->id)->update(['scan_ref' => $ref]);
            }
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP SEQUENCE IF EXISTS diagnoses_scan_seq');
        }

        if (Schema::hasColumn('diagnoses', 'scan_ref')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropColumn('scan_ref');
            });
        }
    }
};
