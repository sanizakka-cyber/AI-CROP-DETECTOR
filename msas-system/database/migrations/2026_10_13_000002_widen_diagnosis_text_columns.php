<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * recovery_period and disease_name were varchar(255). The Phase 11 engine
 * fills recovery_period from a free-text "monitoring and recovery" field,
 * and a longer value made PostgreSQL reject the whole insert: a real,
 * correct diagnosis was lost with HTTP 500 (found in live verification).
 * Both become TEXT; DiagnosisResultMapper also caps the remaining
 * varchar(255) columns so no AI text length can crash a scan again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->text('recovery_period')->nullable()->change();
            $table->text('disease_name')->change();
        });
    }

    public function down(): void
    {
        // Narrowing back to varchar(255) would fail on longer rows; left as TEXT.
    }
};
