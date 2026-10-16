<?php

namespace Tests\Feature;

use App\Jobs\GenerateScanAudio;
use App\Models\Diagnosis;
use App\Models\GlossaryTerm;
use App\Models\ScanResultAudio;
use App\Models\ScanResultTranslation;
use App\Models\SpeechLanguage;
use App\Models\TtsFeedback;
use App\Models\User;
use App\Services\Speech\Providers\ProviderRegistry;
use App\Services\Speech\Providers\TtsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "Listen to your result": translate -> audio job flow, caching, the
 * integrity gate, provider failure and fallback, access rules and feedback.
 * The translator (AI engine) and the voice service are faked over HTTP; the
 * jobs run synchronously (phpunit.xml SPEECH_QUEUE_CONNECTION=sync).
 */
class ScanSpeechTest extends TestCase
{
    use RefreshDatabase;

    private const ENGINE = 'http://engine.test';
    private const TTS = 'http://tts.test';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ai_engine.url' => self::ENGINE,
            'speech.self_hosted.url' => self::TTS,
            'speech.disk'            => 'local',
        ]);
        Storage::fake('local');
        SpeechLanguage::where('code', 'ha')->update(['status' => 'live']);
    }

    // ── fakes ────────────────────────────────────────────────────────────────

    /** Faithful fake: prefixes every string, so numbers and names survive. */
    private function fakeTranslator(?callable $mutate = null): void
    {
        Http::fake([
            self::ENGINE . '/translate/scan-result' => function (HttpRequest $r) use ($mutate) {
                $c = $r['content'];
                $t = [
                    'observations'    => array_map(fn ($s) => "Ƙ: {$s}", $c['observations']),
                    'explanation'     => $c['explanation'] === '' ? '' : "Ƙ: {$c['explanation']}",
                    'recommendations' => array_map(fn ($s) => "Ƙ: {$s}", $c['recommendations']),
                    'safety_note'     => 'Wannan shawarar AI ce kuma tana iya yin kuskure. Tabbatar da jami\'in faɗakarwa ko likitan dabbobi.',
                ];
                return Http::response(['translated' => $mutate ? $mutate($t) : $t, 'model' => 'fake']);
            },
            self::TTS . '/synthesize' => fn (HttpRequest $r) => $this->ttsReply($r),
            '*' => Http::response('unexpected request', 500),
        ]);
    }

    private function ttsReply(HttpRequest $r)
    {
        $n = count($r['segments']);
        return Http::response([
            'format' => 'mp3', 'mime' => 'audio/mpeg', 'audio_base64' => base64_encode('ID3' . str_repeat('x', 100)),
            'size_bytes' => 103, 'duration_sec' => $n * 1.0,
            'segments' => array_map(fn ($i) => ['index' => $i, 'start' => $i * 1.0, 'end' => $i * 1.0 + 0.8], range(0, $n - 1)),
            'engine' => $r['engine'], 'voice' => $r['voice'], 'license' => 'Apache-2.0', 'latency_ms' => 1234,
        ]);
    }

    private function scan(User $owner, array $over = []): Diagnosis
    {
        $f = json_decode(file_get_contents(base_path('../shared-fixtures/canonical-diagnosis.json')), true);
        return Diagnosis::create(array_merge($f['record'], ['user_id' => $owner->id, 'language' => 'en'], $over));
    }

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    private function ttsCalls(): int
    {
        return Http::recorded(fn ($req) => str_starts_with($req->url(), self::TTS))->count();
    }

    private function translatorCalls(): int
    {
        return Http::recorded(fn ($req) => str_starts_with($req->url(), self::ENGINE))->count();
    }

    // ── tests ────────────────────────────────────────────────────────────────

    public function test_language_picker_shows_live_languages_and_the_saved_choice(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'preferred_language' => 'ha']);
        $res = $this->api($farmer)->getJson('/api/speech/languages')->assertOk();

        $this->assertSame(['en', 'ha'], array_column($res->json('languages'), 'code'));
        $this->assertSame('ha', $res->json('default'));
        $this->assertSame('Hausa', $res->json('languages.1.name'));

        $reviewer = User::factory()->create(['role' => 'extension-officer']);
        $codes = array_column($this->api($reviewer)->getJson('/api/speech/languages')->json('languages'), 'code');
        $this->assertSame(['en', 'ha', 'yo', 'ig'], $codes); // in-review languages are visible to reviewers only
        $this->assertSame('Yorùbá', collect($this->api($reviewer)->getJson('/api/speech/languages')->json('languages'))->firstWhere('code', 'yo')['name']);
    }

    public static function scanTypes(): array
    {
        return [['plant'], ['livestock'], ['soil'], ['pest']];
    }

    #[DataProvider('scanTypes')]
    public function test_translate_then_audio_for_every_scan_type(string $type): void
    {
        $this->fakeTranslator();
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer, ['type' => $type]);

        $res = $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha', 'remember' => true])->assertOk();
        $this->assertSame('ready', $res->json('status'));
        $this->assertStringStartsWith('Ƙ: ', $res->json('content.explanation'));
        $this->assertStringContainsString('jami\'in faɗakarwa', $res->json('content.safety_note'));
        $this->assertSame('ha', $farmer->fresh()->preferred_language);

        // Audio was generated right after the translation; GET returns it.
        $audio = $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/ha")->assertOk();
        $this->assertSame('ready', $audio->json('status'));
        $this->assertSame('hausa_feamle1', $audio->json('voice'));
        $this->assertEqualsWithDelta(0.9, $audio->json('rate'), 0.001);
        $this->assertSame(103, $audio->json('sizeBytes'));
        $sentences = $audio->json('sentences');
        $this->assertNotEmpty($sentences);
        $this->assertSame(0.0, (float) $sentences[0]['start']);
        $this->assertStringContainsString('faɗakarwa', end($sentences)['text']); // safety note is spoken last
        $this->assertSame('safety_note', end($sentences)['section']);
        $this->assertSame('observations', $sentences[0]['section']);

        // The signed URL streams the file; download sets an attachment name.
        $this->get($audio->json('url'))->assertOk()->assertHeader('Content-Type', 'audio/mpeg');
        $this->get($audio->json('downloadUrl'))->assertOk()->assertDownload("msas-result-{$d->id}-ha.mp3");
        $this->get(preg_replace('/signature=[^&]+/', 'signature=bad', $audio->json('url')))->assertForbidden();

        // What reached the voice: Hausa text, calm rate, sentence pauses, no segment > 200 chars.
        $sent = Http::recorded(fn ($req) => str_starts_with($req->url(), self::TTS))->first()[0];
        $this->assertSame('ha', $sent['lang']);
        $this->assertEqualsWithDelta(0.9, $sent['rate'], 0.001);
        foreach ($sent['segments'] as $seg) {
            $this->assertLessThanOrEqual(200, mb_strlen($seg['text']));
        }
        $this->assertContains(450, array_column($sent['segments'], 'pause_ms'));
    }

    public function test_translation_and_audio_are_cached(): void
    {
        $this->fakeTranslator();
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();
        $this->assertSame(1, $this->translatorCalls());
        $this->assertSame(1, $this->ttsCalls());

        // Replays and repeat requests: no new translation, no new synthesis.
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();
        $this->api($farmer)->postJson("/api/scans/{$d->id}/audio", ['lang' => 'ha'])->assertOk()->assertJsonPath('status', 'ready');
        $this->assertSame(1, $this->translatorCalls());
        $this->assertSame(1, $this->ttsCalls());

        // A second scan with identical text reuses the stored audio file.
        $d2 = $this->scan($farmer);
        $this->api($farmer)->postJson("/api/scans/{$d2->id}/translate", ['lang' => 'ha'])->assertOk();
        $this->assertSame(2, $this->translatorCalls());
        $this->assertSame(1, $this->ttsCalls());
        $this->assertSame(
            ScanResultAudio::where('diagnosis_id', $d->id)->value('audio_path'),
            ScanResultAudio::where('diagnosis_id', $d2->id)->value('audio_path'),
        );

        // A changed result (e.g. after expert review) is translated again.
        $d->update(['recommended_medication' => 'Mancozeb 80% WP at 2.5 g per litre']);
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();
        $this->assertSame(3, $this->translatorCalls());
    }

    public function test_a_translation_that_changes_a_dose_is_rejected_and_not_spoken(): void
    {
        $this->fakeTranslator(function ($t) {
            $t['recommendations'][] = 'Ƙ: Fesa 25 g kowace lita.'; // invented dose + extra item
            return $t;
        });
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);

        $res = $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();
        $this->assertSame('rejected', $res->json('status'));
        $this->assertNull($res->json('content'));
        $this->assertSame('en', $res->json('source.lang'));
        $this->assertNotEmpty($res->json('source.content.recommendations'));
        $this->assertArrayNotHasKey('problems', $res->json()); // findings are for reviewers only

        $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/ha")->assertOk()->assertJsonPath('status', 'unavailable');
        $this->assertSame(0, $this->ttsCalls());
        $this->assertContains('number 25 was added', ScanResultTranslation::first()->check_problems);
    }

    public function test_when_the_voice_service_fails_the_text_stays_and_a_retry_recovers(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);

        $ttsUp = false;
        Http::fake([
            self::ENGINE . '/translate/scan-result' => fn (HttpRequest $r) => Http::response(['translated' => array_merge($r['content'], ['safety_note' => 'Wannan shawarar AI ce.'])]),
            self::TTS . '/synthesize' => function (HttpRequest $r) use (&$ttsUp) {
                return $ttsUp ? $this->ttsReply($r) : Http::response(['detail' => 'model loading'], 503);
            },
        ]);
        // Same text in a fresh row: make it differ from the cached source by language.
        $res = $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();
        $this->assertSame('ready', $res->json('status'));

        $audio = $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/ha")->assertOk();
        $this->assertSame('failed', $audio->json('status'));
        $this->assertStringContainsString('temporarily unavailable', $audio->json('message'));
        $this->assertNull($audio->json('url'));

        $ttsUp = true;
        $this->api($farmer)->postJson("/api/scans/{$d->id}/audio", ['lang' => 'ha'])->assertOk()->assertJsonPath('status', 'ready');
    }

    public function test_the_fallback_provider_is_used_when_the_primary_fails(): void
    {
        config(['speech.elevenlabs.key' => 'test-key']);
        SpeechLanguage::where('code', 'ha')->update(['fallback_provider' => 'elevenlabs', 'fallback_voice' => 'voice123']);
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        Http::fake([
            self::ENGINE . '/translate/scan-result' => fn (HttpRequest $r) => Http::response(['translated' => array_merge($r['content'], ['safety_note' => 'Wannan shawarar AI ce.'])]),
            self::TTS . '/synthesize' => Http::response(['detail' => 'down'], 503),
            'api.elevenlabs.io/*' => Http::response(str_repeat('a', 4000), 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();
        $audio = $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/ha")->assertOk();
        $this->assertSame('ready', $audio->json('status'));
        $this->assertSame('elevenlabs', $audio->json('provider'));
        Http::assertSent(fn ($req) => str_contains($req->url(), 'text-to-speech/voice123') && $req->hasHeader('xi-api-key', 'test-key'));
    }

    public function test_english_results_need_no_translation(): void
    {
        $this->fakeTranslator();
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $res = $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'en'])->assertOk();
        $this->assertSame('ready', $res->json('status'));
        $this->assertSame(config('speech.safety_note_en'), $res->json('content.safety_note'));
        $this->assertSame(0, $this->translatorCalls());
        $this->assertSame('idera', $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/en")->json('voice'));
    }

    public function test_a_reviewed_safety_note_replaces_the_machine_translation(): void
    {
        $this->fakeTranslator();
        SpeechLanguage::where('code', 'ha')->update(['safety_note' => 'Shawarar AI ce. Ka tuntuɓi jami\'in gona.']);
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $res = $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();
        $this->assertSame('Shawarar AI ce. Ka tuntuɓi jami\'in gona.', $res->json('content.safety_note'));
    }

    public function test_access_rules(): void
    {
        $this->fakeTranslator();
        $owner = User::factory()->create(['role' => 'farmer']);
        $other = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $reviewer = User::factory()->create(['role' => 'extension-officer']);
        $d = $this->scan($owner);

        $this->api($other)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertForbidden();
        $this->api($owner)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'yo'])->assertStatus(422); // not live yet
        $this->api($owner)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();

        // Dealers use it for their own scans.
        $mine = $this->scan($dealer);
        $this->api($dealer)->postJson("/api/scans/{$mine->id}/translate", ['lang' => 'ha'])->assertOk();

        // Only extension staff (and admins) mark translations reviewed.
        $this->api($owner)->postJson("/api/scans/{$d->id}/translation/ha/review")->assertForbidden();
        $this->api($reviewer)->postJson("/api/scans/{$d->id}/translation/ha/review")
            ->assertOk()->assertJsonPath('reviewedCount', 1)->assertJsonPath('target', 50);
        $this->assertNotNull(ScanResultTranslation::where('diagnosis_id', $d->id)->value('reviewed_at'));
        $this->assertSame([], $this->api($reviewer)->getJson("/api/scans/{$d->id}/translation/ha")->json('problems'));
    }

    public function test_feedback_is_saved_per_language_and_voice(): void
    {
        $this->fakeTranslator();
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();

        $this->api($farmer)->postJson('/api/tts-feedback', ['scan_id' => $d->id, 'lang' => 'ha', 'clear' => false, 'comment' => 'Too fast'])->assertCreated();
        $this->api($farmer)->postJson('/api/tts-feedback', ['scan_id' => $d->id, 'lang' => 'ha', 'clear' => true])->assertCreated();

        $fb = TtsFeedback::sole();
        $this->assertTrue($fb->clear);
        $this->assertSame('hausa_feamle1', $fb->voice);
        $this->assertSame('yarngpt', $fb->provider);

        $other = User::factory()->create(['role' => 'farmer']);
        $this->api($other)->postJson('/api/tts-feedback', ['scan_id' => $d->id, 'lang' => 'ha', 'clear' => true])->assertForbidden();
    }

    public function test_jobs_go_to_the_speech_queue_and_requests_return_immediately(): void
    {
        Queue::fake();
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertStatus(202)->assertJsonPath('status', 'pending');
        Queue::assertPushedOn('speech', \App\Jobs\TranslateScanResult::class);

        // Polling while it runs.
        $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/ha")->assertOk()->assertJsonPath('status', 'translating');
        // A second click does not queue a duplicate job.
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertStatus(202);
        Queue::assertPushed(\App\Jobs\TranslateScanResult::class, 1);
    }

    public function test_a_language_goes_live_only_after_native_speaker_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $yo = SpeechLanguage::where('code', 'yo')->first();
        $form = ['name_english' => 'Yoruba', 'name_native' => 'Yorùbá', 'status' => 'live', 'provider' => 'yarngpt', 'voice' => 'yoruba_male2', 'rate' => 0.9];

        $this->actingAs($admin)->patch(route('admin.speech.languages.update', $yo), $form)->assertSessionHasErrors('status');
        $this->assertSame('in_review', $yo->fresh()->status);

        $farmer = User::factory()->create(['role' => 'farmer']);
        foreach (range(1, 50) as $i) {
            $d = $this->scan($farmer);
            ScanResultTranslation::create(['diagnosis_id' => $d->id, 'lang' => 'yo', 'source_hash' => str_repeat('a', 64),
                'status' => 'ready', 'translated_json' => [], 'reviewed_at' => now(), 'reviewed_by' => $admin->id]);
        }
        $this->actingAs($admin)->patch(route('admin.speech.languages.update', $yo), $form)->assertSessionHasNoErrors();
        $this->assertSame('live', $yo->fresh()->status);
    }

    public function test_admin_adds_a_language_and_glossary_without_code_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.speech.languages.store'), [
            'code' => 'pcm', 'name_english' => 'Nigerian Pidgin', 'name_native' => 'Naijá', 'rate' => 0.9,
        ])->assertSessionHasNoErrors();
        $this->assertSame('draft', SpeechLanguage::where('code', 'pcm')->value('status'));

        $this->actingAs($admin)->post(route('admin.speech.glossary.store', 'ha'), ['kind' => 'glossary', 'english_term' => 'fall armyworm', 'local_term' => 'tsutsar fall armyworm'])->assertSessionHasNoErrors();
        $this->assertSame('tsutsar fall armyworm', GlossaryTerm::where('lang', 'ha')->value('local_term'));

        $farmer = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($farmer)->get(route('admin.speech.index'))->assertRedirect();
    }

    public function test_glossary_and_protected_terms_reach_the_translator(): void
    {
        $this->fakeTranslator();
        GlossaryTerm::create(['lang' => 'ha', 'kind' => 'glossary', 'english_term' => 'leaf', 'local_term' => 'ganye']);
        GlossaryTerm::create(['lang' => '*', 'kind' => 'protected', 'english_term' => 'Ridomil Gold', 'local_term' => 'Ridomil Gold']);
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'ha'])->assertOk();

        Http::assertSent(fn ($req) => str_ends_with($req->url(), '/translate/scan-result')
            && $req['target_language_name'] === 'Hausa'
            && $req['glossary'] === [['english_term' => 'leaf', 'local_term' => 'ganye']]
            && in_array('Ridomil Gold', $req['protected_terms'], true));
    }

    public function test_non_commercial_providers_cannot_be_used(): void
    {
        config(['speech.providers.farmerline.commercial_use' => false]);
        $this->assertArrayNotHasKey('farmerline', ProviderRegistry::selectable());
        $this->expectException(TtsException::class);
        ProviderRegistry::make('farmerline');
    }

    public function test_history_page_shows_the_listen_panel_with_no_auto_play(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'preferred_language' => 'ha']);
        $this->scan($farmer);
        $html = $this->actingAs($farmer)->get(route('diagnostics.history'))->assertOk()->getContent();

        $this->assertStringContainsString('Listen to your result', $html);
        $this->assertMatchesRegularExpression('/<option value="ha"\s+selected/', $html); // saved choice is the default
        $this->assertStringNotContainsString('autoplay', $html);
        $this->assertStringNotContainsString('ttsPlay', $html); // the old browser-speech player is gone
        $this->assertSame(0, $this->translatorCalls() + $this->ttsCalls()); // nothing generated until the farmer asks
    }

    public function test_web_routes_use_the_same_flow(): void
    {
        $this->fakeTranslator();
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $this->actingAs($farmer)->postJson(route('speech.translate', $d), ['lang' => 'ha'])->assertOk()->assertJsonPath('status', 'ready');
        $this->actingAs($farmer)->getJson(route('speech.audio.show', [$d, 'ha']))->assertOk()->assertJsonPath('status', 'ready');
    }
}
