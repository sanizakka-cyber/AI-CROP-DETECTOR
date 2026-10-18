<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Listen to your result": languages, stored translations, generated audio,
 * glossary/pronunciation terms and listener feedback.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('speech_languages')) {
            Schema::create('speech_languages', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();         // ISO 639 code, e.g. ha, yo, ig, pcm
                $table->string('name_english', 60);
                $table->string('name_native', 60);              // shown in the picker, e.g. Yorùbá
                $table->string('status', 20)->default('draft'); // draft | in_review | live | disabled
                $table->string('provider', 30)->nullable();
                $table->string('voice', 60)->nullable();
                $table->string('fallback_provider', 30)->nullable();
                $table->string('fallback_voice', 60)->nullable();
                $table->decimal('rate', 3, 2)->default(0.90);
                $table->text('safety_note')->nullable();        // reviewed wording; empty = translated with each result
                $table->unsignedSmallInteger('sort')->default(100);
                $table->timestamps();
            });

            $now = now();
            foreach (config('speech.languages', []) as $lang) {
                DB::table('speech_languages')->insert(array_merge($lang, [
                    'rate' => config('speech.default_rate', 0.9), 'created_at' => $now, 'updated_at' => $now,
                ]));
            }
        }

        if (!Schema::hasTable('scan_result_translations')) {
            Schema::create('scan_result_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
                $table->string('lang', 10);
                $table->string('source_lang', 10)->default('en');
                $table->char('source_hash', 64);          // a changed result (e.g. expert edit) is re-translated
                $table->json('source_json')->nullable();
                $table->json('translated_json')->nullable();
                $table->string('status', 20)->default('pending'); // pending | ready | failed | rejected
                $table->json('check_problems')->nullable();       // integrity findings (numbers, units, products)
                $table->string('model', 80)->nullable();
                $table->text('error')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->unique(['diagnosis_id', 'lang']);
            });
        }

        if (!Schema::hasTable('scan_result_audio')) {
            Schema::create('scan_result_audio', function (Blueprint $table) {
                $table->id();
                $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
                $table->string('lang', 10);
                $table->string('voice', 60);
                $table->string('provider', 30)->nullable();
                $table->decimal('rate', 3, 2);
                $table->char('cache_key', 64)->nullable(); // hash(spoken text + lang + voice + provider + rate)
                $table->string('audio_path')->nullable();
                $table->string('mime', 30)->nullable();
                $table->decimal('duration_sec', 8, 2)->nullable();
                $table->unsignedInteger('size_bytes')->nullable();
                $table->json('segments')->nullable();     // [{text, start, end}] for sentence highlighting
                // Sentence-by-sentence audio, playable before the whole file is ready:
                // [{index, sentence, path, mime, duration_sec, size_bytes, speech_start, speech_end}]
                $table->json('chunks')->nullable();
                $table->unsignedSmallInteger('chunks_total')->nullable();
                $table->unsignedInteger('first_chunk_ms')->nullable(); // time to first playable audio
                $table->string('status', 20)->default('pending'); // pending | ready | failed
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedInteger('latency_ms')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
                $table->unique(['diagnosis_id', 'lang', 'voice', 'rate']);
                $table->index('cache_key');
            });
        }

        if (!Schema::hasTable('glossary_terms')) {
            Schema::create('glossary_terms', function (Blueprint $table) {
                $table->id();
                $table->string('lang', 10);
                // glossary: English term -> local term used when translating.
                // pronunciation: written form -> how the voice should say it.
                $table->string('kind', 20)->default('glossary');
                $table->string('english_term', 120);
                $table->string('local_term', 200);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['lang', 'kind', 'english_term']);
            });
        }

        if (!Schema::hasTable('tts_feedback')) {
            Schema::create('tts_feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
                $table->string('lang', 10);
                $table->string('voice', 60)->nullable();
                $table->string('provider', 30)->nullable();
                $table->boolean('clear');
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'diagnosis_id', 'lang']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tts_feedback');
        Schema::dropIfExists('glossary_terms');
        Schema::dropIfExists('scan_result_audio');
        Schema::dropIfExists('scan_result_translations');
        Schema::dropIfExists('speech_languages');
    }
};
