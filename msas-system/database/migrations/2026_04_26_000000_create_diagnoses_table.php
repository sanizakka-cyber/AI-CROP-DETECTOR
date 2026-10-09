<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'plant' or 'animal'
            $table->string('image_path')->nullable();
            
            // Subject identification (auto-detected by AI)
            $table->string('subject_name')->nullable();
            $table->string('scientific_name')->nullable();
            $table->string('detected_part')->nullable();
            $table->string('health_status')->nullable();
            $table->string('severity_level')->nullable();

            // Core diagnosis
            $table->string('disease_name');
            // Nullable from creation — a later migration
            // (2026_08_18_000001_add_scan_ref_and_nullable_confidence_to_
            // diagnoses.php) drops the NOT NULL constraint this would
            // otherwise create, but ONLY on Postgres, since SQLite has no
            // ALTER COLUMN at all. Production already ran that later
            // migration and is unaffected by this edit (already-applied
            // migrations never re-run); every FRESH migration run — every
            // CI job and local SQLite test database — previously recreated
            // this column as NOT NULL with no way to ever make it nullable
            // again, so any code path that honestly stores "no score yet"
            // (DiagnosisResultMapper::aiUnavailableFallback(), the AI
            // engine unavailable path) crashed with a NOT NULL constraint
            // violation on every single test run. That code path had
            // therefore never once been exercised by a test until this
            // audit (2026-10-09) ran into it directly.
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('urgency_level')->default('Medium');

            // Detailed findings
            $table->text('symptoms_identified')->nullable();
            $table->text('cause')->nullable();
            $table->text('environmental_factors')->nullable();
            $table->text('nutrient_deficiencies')->nullable();
            $table->text('pest_detection')->nullable();

            // Treatment & prevention
            $table->text('first_aid_steps')->nullable();
            $table->text('recommended_medication')->nullable();
            $table->text('preventive_measures')->nullable();
            $table->text('fertilizer_recommendation')->nullable();
            $table->string('recovery_period')->nullable();
            $table->text('best_practices')->nullable();
            $table->text('vet_referral_advice')->nullable();

            // Explainable AI
            $table->text('explanation')->nullable();

            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
