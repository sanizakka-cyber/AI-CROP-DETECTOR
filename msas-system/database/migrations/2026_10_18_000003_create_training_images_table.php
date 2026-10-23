<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12.2: labelled photos contributed by partners (vets, extension
 * officers) or taken from openly licensed datasets, for the accuracy and
 * consistency evaluation (ai-engine/eval/run_eval.py). Every row records
 * where the photo came from and under what licence or written consent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_images')) {
            return;
        }
        Schema::create('training_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('mode', 16);                       // crop|livestock|soil|pest (scan mode)
            $table->string('species', 40)->nullable();
            $table->string('condition_label', 150)->nullable(); // null only when expected_outcome is set
            $table->string('synonyms', 300)->nullable();      // "|"-separated, as in the eval manifest
            $table->string('expected_outcome', 32)->nullable(); // poor_image|unsupported_subject
            $table->string('label_basis', 20);                // lab_confirmed|vet_diagnosis|suspected
            $table->string('body_part', 60)->nullable();
            $table->string('state', 60)->nullable();
            $table->string('lga', 80)->nullable();
            $table->date('captured_on')->nullable();
            $table->string('source_type', 20);                // field_partner|public_dataset
            $table->string('source_name', 200)->nullable();
            $table->string('source_url', 300)->nullable();
            $table->string('licence', 30);
            $table->string('consent_reference', 100)->nullable();
            $table->boolean('consent_confirmed')->default(false);
            $table->string('consent_form_path')->nullable();
            $table->string('disk', 20);
            $table->string('image_path');
            $table->char('sha256', 64)->unique();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('review_status', 16)->default('pending')->index(); // pending|approved|rejected|withdrawn
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_images');
    }
};
