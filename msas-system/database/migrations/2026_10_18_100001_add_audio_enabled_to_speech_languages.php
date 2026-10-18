<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Launch decisions (2026-10-18, docs/listen-to-result.md):
 *  - audio is switched on per language (audio_enabled); a language without
 *    audio still shows its translated text, and Listen is hidden for it;
 *  - English speaks with Kokoro-82M (af_heart), Hausa with OpenBible, both
 *    on CPU; YarnGPT2 is not used in production on CPU;
 *  - Yoruba and Igbo are text only for now; their OpenBible voices stay set
 *    as the candidates for later review, with audio off.
 * Only rows still holding the original seed (provider yarngpt) are changed,
 * so nothing an admin set by hand is overwritten.
 */
return new class extends Migration
{
    private const LAUNCH = [
        'en' => ['provider' => 'kokoro',    'voice' => 'af_heart',         'audio_enabled' => true],
        'ha' => ['provider' => 'openbible', 'voice' => 'openbible_hausa',  'audio_enabled' => true],
        'yo' => ['provider' => 'openbible', 'voice' => 'openbible_yoruba', 'audio_enabled' => false],
        'ig' => ['provider' => 'openbible', 'voice' => 'openbible_igbo',   'audio_enabled' => false],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('speech_languages')) {
            return;
        }
        if (!Schema::hasColumn('speech_languages', 'audio_enabled')) {
            Schema::table('speech_languages', function (Blueprint $table) {
                $table->boolean('audio_enabled')->default(false);
            });
        }
        foreach (self::LAUNCH as $code => $set) {
            DB::table('speech_languages')->where('code', $code)->where('provider', 'yarngpt')
                ->update($set + ['updated_at' => now()]);
            // A language whose provider an admin already changed keeps it; only audio follows the decision.
            DB::table('speech_languages')->where('code', $code)->update(['audio_enabled' => $set['audio_enabled']]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('speech_languages') || !Schema::hasColumn('speech_languages', 'audio_enabled')) {
            return;
        }
        $seed = collect(config('speech.yarngpt_seed_voices', [
            'en' => 'idera', 'ha' => 'hausa_female1', 'yo' => 'yoruba_male2', 'ig' => 'igbo_female2',
        ]));
        foreach (self::LAUNCH as $code => $set) {
            DB::table('speech_languages')->where('code', $code)->where('provider', $set['provider'])->where('voice', $set['voice'])
                ->update(['provider' => 'yarngpt', 'voice' => $seed[$code]]);
        }
        Schema::table('speech_languages', function (Blueprint $table) {
            $table->dropColumn('audio_enabled');
        });
    }
};
