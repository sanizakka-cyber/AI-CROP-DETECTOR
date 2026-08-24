<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Monitoring & Evaluation officer role accumulated four different
 * spellings across the codebase over time ('m-e-officer', 'me-officer',
 * 'monitoring-evaluation', 'me_officer') — every account seeder assigns
 * only 'm-e-officer' (see App\Support\Roles's doc comment), which is the
 * canonical form the app's own checks resolve every synonym to. Legacy
 * checks scattered through the codebase were fixed to canonicalize on
 * read (App\Support\Roles::canonical()), but the preferred long-term fix
 * is normalizing the stored data itself so those checks become
 * unnecessary defensive code rather than a load-bearing correction for
 * bad data. This migration is idempotent and safe to run multiple times —
 * it only ever moves rows *toward* the canonical spelling.
 */
return new class extends Migration
{
    private const LEGACY_TO_CANONICAL = [
        'me-officer'            => 'm-e-officer',
        'monitoring-evaluation' => 'm-e-officer',
        'me_officer'            => 'm-e-officer',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_TO_CANONICAL as $legacy => $canonical) {
            DB::table('users')->where('role', $legacy)->update(['role' => $canonical]);
        }
    }

    public function down(): void
    {
        // Deliberately not reversible — the legacy spellings were never
        // meaningfully distinct from 'm-e-officer', so there is nothing
        // correct to roll back to (which specific old spelling a given row
        // used is not preserved once merged, by design).
    }
};
