<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12.2: the engine runs each scan several times and combines the
 * answers (ai-engine/consensus.py). This keeps how they agreed (passes,
 * each pass's finding and confidence, the spread) for audit and analytics.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('diagnoses', 'ai_consensus')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->json('ai_consensus')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('diagnoses', 'ai_consensus')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropColumn('ai_consensus');
            });
        }
    }
};
