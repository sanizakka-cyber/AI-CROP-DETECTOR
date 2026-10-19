<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Photo too unclear" was stored for ANY engine 4xx, including a website
 * to engine key mismatch (401) and files the engine could not decode, so
 * the AI never looked at those photos. Rule now: "Photo too unclear" only
 * when the AI ran and judged the photo (IMAGE_UNUSABLE).
 *
 * Rows stored that way (outcome poor_image, no AI model recorded) become
 * failed scans (outcome provider_error, decision ai_unavailable, so not
 * counted), except older-engine "does not appear to show a plant" replies,
 * which the AI did make: those become unsupported_subject. Every changed
 * row's previous values are kept in scan_relabel_backfill; down() restores them.
 */
return new class extends Migration
{
    private const COLUMNS = ['assessment_outcome', 'confidence_decision', 'disease_name', 'photo_tips', 'image_quality', 'urgency_level'];

    public function up(): void
    {
        if (!Schema::hasTable('scan_relabel_backfill')) {
            Schema::create('scan_relabel_backfill', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('diagnosis_id')->index();
                $table->json('old_values');
                $table->timestamp('created_at')->useCurrent();
            });
        }
        DB::table('diagnoses')->where('assessment_outcome', 'poor_image')->whereNull('ai_model_name')
            ->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $full = (array) DB::table('diagnoses')->where('id', $row->id)->first(self::COLUMNS + ['explanation' => 'explanation']);
                    $judged = is_string($full['explanation'] ?? null) && preg_match('/does not appear to show|not a (plant|crop|animal|pest|soil)/i', $full['explanation']);
                    $new = $judged
                        ? ['assessment_outcome' => 'unsupported_subject', 'confidence_decision' => 'unsupported_subject',
                           'disease_name' => 'Photo does not show the scanned subject']
                        : ['assessment_outcome' => 'provider_error', 'confidence_decision' => 'ai_unavailable',
                           'disease_name' => 'Scan did not complete', 'photo_tips' => null, 'image_quality' => null, 'urgency_level' => null];
                    DB::table('scan_relabel_backfill')->insert([
                        'diagnosis_id' => $row->id,
                        'old_values'   => json_encode(array_intersect_key($full, array_flip(self::COLUMNS))),
                    ]);
                    DB::table('diagnoses')->where('id', $row->id)->update($new);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('scan_relabel_backfill')) {
            return;
        }
        DB::table('scan_relabel_backfill')->chunkByIdDesc(500, function ($rows) {
            foreach ($rows as $b) {
                DB::table('diagnoses')->where('id', $b->diagnosis_id)->update(json_decode($b->old_values, true));
            }
        });
        Schema::drop('scan_relabel_backfill');
    }
};
