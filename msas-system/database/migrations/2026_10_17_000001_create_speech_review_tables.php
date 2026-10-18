<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Native-speaker review of spoken results ("Listen to your result").
 *
 * - speech_reviewers: who reviews which language, and as what (a native
 *   speaker, or an extension officer / vet who checks the farming content).
 * - speech_review_packs: a set of items for one language, with the voices
 *   being compared. Reviewers see them only as A/B/C.
 * - speech_review_items: one anonymised scan result (source + translation +
 *   one audio file per voice), or a list of numbers/units, screen labels,
 *   the spoken safety note, or pronunciations.
 * - speech_reviews: one reviewer's ratings, marked errors and preferred voice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speech_reviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('lang', 10);
            $table->string('kind', 20); // native | expert
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'lang']);
        });

        Schema::create('speech_review_packs', function (Blueprint $table) {
            $table->id();
            $table->string('lang', 10)->index();
            $table->string('title', 120);
            $table->string('status', 20)->default('open'); // open | closed
            $table->json('candidates');                   // [{provider, voice, license}] in build order (hidden from reviewers)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('speech_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained('speech_review_packs')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('kind', 20);                   // scan | numbers | labels | safety_note | lexicon
            $table->string('scan_type', 20)->nullable();  // crop | livestock | soil | pest (kind = scan)
            $table->foreignId('diagnosis_id')->nullable()->constrained()->nullOnDelete(); // never shown to reviewers
            $table->string('source_lang', 10)->nullable();
            $table->json('source_json');                  // what was translated (anonymised)
            $table->json('translation_json');             // what the farmer would read and hear
            $table->json('label_map');                    // {"A": candidate index, ...}, shuffled per item
            $table->json('audio')->nullable();            // {"A": {path, mime, duration_sec, size_bytes} | {error}}
            $table->timestamps();
            $table->unique(['pack_id', 'position']);
        });

        Schema::create('speech_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('speech_review_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reviewer_kind', 20);
            $table->unsignedTinyInteger('accuracy')->nullable();      // translation accuracy 1-5 (the text, once per item)
            // Per voice label: {"A": {naturalness, pronunciation, speed}}, each 1-5 (speed 3 = right).
            $table->json('voice_ratings')->nullable();
            $table->char('preferred_label', 1)->nullable();
            $table->unsignedSmallInteger('preferred_candidate')->nullable(); // resolved from the label at save time
            $table->json('errors')->nullable();          // [{section, quote, type, note}]
            $table->unsignedSmallInteger('dosage_errors')->default(0); // errors of type dosage or chemical
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speech_reviews');
        Schema::dropIfExists('speech_review_items');
        Schema::dropIfExists('speech_review_packs');
        Schema::dropIfExists('speech_reviewers');
    }
};
