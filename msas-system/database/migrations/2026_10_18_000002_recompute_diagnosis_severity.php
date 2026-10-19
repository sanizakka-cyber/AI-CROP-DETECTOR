<?php

use App\Support\Severity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12.2: recompute every stored severity_level from its source, the
 * model's own reply (raw_model_output), so old rows follow the same single
 * severity rule as new ones (App\Support\Severity).
 *
 * - A finding-less result (poor photo, wrong subject, AI outage) has no severity.
 * - Otherwise the severity the model wrote, normalised. A legacy reply with
 *   no severity line had "Moderate" filled in by the old parser; that
 *   invented value becomes null.
 * - Rows without a raw reply keep their value, normalised.
 *
 * Nothing is lost: each changed row's previous value is kept in
 * diagnosis_severity_backfill, and down() puts every one back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnosis_severity_backfill')) {
            Schema::create('diagnosis_severity_backfill', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('diagnosis_id')->index();
                $table->string('old_value')->nullable();
                $table->string('new_value')->nullable();
                $table->string('source', 32);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        DB::table('diagnoses')
            ->select('id', 'severity_level', 'raw_model_output', 'assessment_outcome')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    [$new, $source] = $this->recompute($row);
                    if ($new === $row->severity_level) {
                        continue;
                    }
                    DB::table('diagnosis_severity_backfill')->insert([
                        'diagnosis_id' => $row->id, 'old_value' => $row->severity_level,
                        'new_value' => $new, 'source' => $source,
                    ]);
                    DB::table('diagnoses')->where('id', $row->id)->update(['severity_level' => $new]);
                }
            });
    }

    private function recompute(object $row): array
    {
        if (in_array($row->assessment_outcome, ['poor_image', 'unsupported_subject', 'provider_error'], true)) {
            return [null, 'no_finding'];
        }
        $fromRaw = Severity::fromRawModelOutput($row->raw_model_output);
        if ($fromRaw !== false) {
            return [$fromRaw, 'raw_model_output'];
        }
        if (is_string($row->raw_model_output) && trim($row->raw_model_output) !== '') {
            // The model replied but never stated a severity: the stored value was a parser default.
            return [null, 'not_in_reply'];
        }
        return [Severity::normalize($row->severity_level), 'normalised'];
    }

    public function down(): void
    {
        if (!Schema::hasTable('diagnosis_severity_backfill')) {
            return;
        }
        // Newest first, so the oldest recorded value is the one left in place.
        DB::table('diagnosis_severity_backfill')->chunkByIdDesc(500, function ($rows) {
            foreach ($rows as $b) {
                DB::table('diagnoses')->where('id', $b->diagnosis_id)->update(['severity_level' => $b->old_value]);
            }
        });
        Schema::drop('diagnosis_severity_backfill');
    }
};
