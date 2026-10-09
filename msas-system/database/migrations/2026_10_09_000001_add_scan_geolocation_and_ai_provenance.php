<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full-system re-audit (2026-10-09), Sections 4.5 and 5.
 *
 * Two additive, nullable-only changes — no existing column is altered or
 * rewritten, so every historical diagnosis remains exactly as it was:
 *
 * 1. Confidence provenance columns on `diagnoses`. Before this migration the
 *    only AI-related fact stored was the final `confidence_score` number
 *    itself — nothing recorded WHAT produced it, so there was no way to
 *    audit, display calibration status, or distinguish a primary-model
 *    result from a fallback/human one. These columns are honest about what
 *    is actually known: `validation_status` defaults to 'unvalidated'
 *    because no labelled evaluation dataset exists for this AI engine (it
 *    is a vision-language model prompted to self-report a number, not a
 *    trained classifier with class probabilities — see ai-engine/main.py).
 *    Historical rows get these columns as NULL, not a fabricated backfill.
 *
 * 2. `collection_locations` — one row per diagnosis (nullable 1:1), carrying
 *    the sample's actual collection geography, separate from the scan's
 *    processing time/place. A diagnosis with no location row simply has no
 *    location recorded — never silently defaulted to the user's registered
 *    address, device IP, or server region.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('diagnoses') && !Schema::hasColumn('diagnoses', 'ai_model_name')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                // ── AI confidence provenance (spec 4.5) ───────────────────────
                $table->string('ai_model_name')->nullable()->after('explanation');
                $table->string('ai_model_version')->nullable()->after('ai_model_name');
                $table->text('raw_model_output')->nullable()->after('ai_model_version');
                $table->text('confidence_interpretation')->nullable()->after('raw_model_output');
                // sufficient | low_confidence_review | unsupported_sample |
                // poor_image_quality | non_agricultural_image | ambiguous |
                // model_timeout | malformed_response | ai_unavailable
                $table->string('confidence_decision')->nullable()->after('confidence_interpretation');
                $table->decimal('decision_threshold', 5, 2)->nullable()->after('confidence_decision');
                // 'unvalidated' is the honest default — see class docblock above.
                $table->string('validation_status')->default('unvalidated')->after('decision_threshold');
                // primary_model | fallback_model | human_reviewer
                $table->string('generated_by')->nullable()->after('validation_status');

                // ── Optional research metadata (spec 6D / 7) ──────────────────
                // Free-form, optional, configurable-by-role fields (research
                // project id, specimen id, growth stage, pre-scan symptoms,
                // treatment history, weather at collection, etc). Kept as one
                // JSON column rather than a dozen near-always-empty columns —
                // ordinary farmers never populate this; field officers /
                // researchers can via a dedicated optional form section.
                $table->json('research_metadata')->nullable()->after('generated_by');
                // null = not asked/unknown, true/false = explicit answer.
                $table->boolean('consent_research_use')->nullable()->after('research_metadata');
            });
        }

        if (!Schema::hasTable('collection_locations')) {
            Schema::create('collection_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('diagnosis_id')->unique()->constrained('diagnoses')->cascadeOnDelete();

                // ── Administrative location ────────────────────────────────────
                $table->string('country')->default('Nigeria');
                $table->string('state')->nullable();
                $table->string('lga')->nullable();
                $table->string('ward')->nullable();
                $table->string('community')->nullable();
                $table->string('postal_code')->nullable();
                // Where postal_code came from: e.g. 'user_entered' — never
                // inferred from LGA alone without an authoritative source.
                $table->string('postal_code_source')->nullable();
                $table->text('address_landmark')->nullable();

                // ── Coordinates ───────────────────────────────────────────────
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('accuracy_meters', 10, 2)->nullable();
                $table->decimal('elevation_meters', 8, 2)->nullable();

                // ── Provenance (spec 5.5) ────────────────────────────────────────
                // gps_device | manual_coordinates | administrative_selection |
                // address_entry | unavailable
                $table->string('capture_method')->nullable();
                // device | manual | geocoded
                $table->string('coordinate_source')->nullable();
                $table->boolean('user_confirmed')->default(false);
                // unverified | user_confirmed | boundary_mismatch_flagged
                $table->string('verification_status')->default('unverified');
                // True when the farmer explicitly said "I'm scanning this
                // somewhere other than where I collected it" (spec 5.1 #9).
                $table->boolean('differs_from_scan_location')->default(false);

                // ── Timing ────────────────────────────────────────────────────
                $table->timestamp('collected_at')->nullable();
                $table->string('timezone')->nullable();

                // ── Dataset / collector provenance ───────────────────────────
                $table->string('location_dataset_version')->nullable();
                $table->string('collector_identifier')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['state', 'lga']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_locations');

        if (Schema::hasTable('diagnoses') && Schema::hasColumn('diagnoses', 'ai_model_name')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropColumn([
                    'ai_model_name', 'ai_model_version', 'raw_model_output',
                    'confidence_interpretation', 'confidence_decision', 'decision_threshold',
                    'validation_status', 'generated_by', 'research_metadata', 'consent_research_use',
                ]);
            });
        }
    }
};
