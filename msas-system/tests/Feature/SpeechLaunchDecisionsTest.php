<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\SpeechLanguage;
use App\Models\SpeechReview;
use App\Models\SpeechReviewItem;
use App\Models\SpeechReviewPack;
use App\Models\SpeechReviewer;
use App\Models\User;
use App\Services\Speech\GoLiveRule;
use App\Services\Speech\Providers\ProviderRegistry;
use App\Services\Speech\ReviewPackBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Launch decisions for "Listen to your result" (2026-10-18,
 * docs/listen-to-result.md):
 *  - voice in English (Kokoro-82M) and Hausa (OpenBible) only, on CPU;
 *  - Yoruba and Igbo: translated text only, Listen hidden, never broken;
 *  - YarnGPT2 not used in production on CPU;
 *  - pre-generation on, for the preferred language only, only where it has audio;
 *  - go-live: packs of 25, two approved packs; naturalness only with audio.
 */
class SpeechLaunchDecisionsTest extends TestCase
{
    use RefreshDatabase;

    private const ENGINE = 'http://engine.test';
    private const CPU = 'http://tts-cpu.test';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ai_engine.url' => self::ENGINE,
            'speech.providers.kokoro.url' => self::CPU,
            'speech.providers.openbible.url' => self::CPU,
            'speech.disk' => 'local',
        ]);
        Storage::fake('local');
        Http::fake([
            self::ENGINE . '/translate/scan-result' => fn (HttpRequest $r) => Http::response(['model' => 'fake', 'translated' => [
                'observations' => array_map(fn ($s) => "T: {$s}", $r['content']['observations']),
                'explanation' => $r['content']['explanation'] === '' ? '' : "T: {$r['content']['explanation']}",
                'recommendations' => array_map(fn ($s) => "T: {$s}", $r['content']['recommendations']),
                'safety_note' => 'T: safety',
            ]]),
            self::CPU . '/synthesize' => fn (HttpRequest $r) => Http::response([
                'format' => 'mp3', 'mime' => 'audio/mpeg', 'audio_base64' => base64_encode('ID3' . str_repeat('x', 50)),
                'size_bytes' => 53, 'duration_sec' => count($r['segments']) * 1.0,
                'segments' => array_map(fn ($i) => ['index' => $i, 'start' => $i, 'end' => $i + 0.8], array_keys($r['segments'])),
                'engine' => $r['engine'], 'voice' => $r['voice'], 'license' => 'x', 'latency_ms' => 5,
            ]),
            '*' => Http::response('unexpected', 500),
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

    public function test_launch_voices_english_kokoro_hausa_openbible_yoruba_igbo_text_only(): void
    {
        $langs = SpeechLanguage::orderBy('sort')->get()->keyBy('code');
        $this->assertSame(['kokoro', 'af_heart', true], [$langs['en']->provider, $langs['en']->voice, $langs['en']->hasAudio()]);
        $this->assertSame(['openbible', 'openbible_hausa', true], [$langs['ha']->provider, $langs['ha']->voice, $langs['ha']->hasAudio()]);
        foreach (['yo', 'ig'] as $c) {
            $this->assertSame('openbible', $langs[$c]->provider, "{$c} keeps its candidate voice for review");
            $this->assertFalse($langs[$c]->hasAudio(), "{$c} is text only");
        }

        $reviewer = User::factory()->create(['role' => 'extension-officer']);
        $res = $this->api($reviewer)->getJson('/api/speech/languages')->assertOk();
        $voice = collect($res->json('languages'))->pluck('hasVoice', 'code')->all();
        $this->assertSame(['en' => true, 'ha' => true, 'yo' => false, 'ig' => false], $voice);
        // Credits are for the voices actually spoken.
        $credited = array_column($res->json('credits'), 'provider');
        $this->assertEqualsCanonicalizing(['kokoro', 'openbible'], $credited);
        $this->assertStringContainsString('CC BY-SA 4.0', collect($res->json('credits'))->firstWhere('provider', 'openbible')['credit']);
    }

    public function test_yarngpt_is_not_used_in_production_unless_switched_on(): void
    {
        $this->assertArrayNotHasKey('yarngpt', ProviderRegistry::selectable());
        $this->assertArrayHasKey('kokoro', ProviderRegistry::selectable());
        config(['speech.providers.yarngpt.enabled' => true]);
        $this->assertArrayHasKey('yarngpt', ProviderRegistry::selectable());

        // A language left on YarnGPT2 has no audio while it is off.
        config(['speech.providers.yarngpt.enabled' => false]);
        SpeechLanguage::where('code', 'en')->update(['provider' => 'yarngpt', 'voice' => 'idera']);
        $this->assertFalse(SpeechLanguage::findCode('en')->hasAudio());
    }

    public function test_english_audio_is_spoken_by_kokoro_on_the_cpu_service(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);
        $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'en'])->assertOk();
        $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/en")->assertOk()->assertJsonPath('status', 'ready')->assertJsonPath('voice', 'af_heart');
        Http::assertSent(fn ($r) => $r->url() === self::CPU . '/synthesize' && $r['engine'] === 'kokoro' && $r['voice'] === 'af_heart' && $r['lang'] === 'en');
    }

    public function test_a_text_only_language_gives_the_translation_and_never_asks_for_audio(): void
    {
        SpeechLanguage::where('code', 'yo')->update(['status' => 'live']);
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($farmer);

        $t = $this->api($farmer)->postJson("/api/scans/{$d->id}/translate", ['lang' => 'yo'])->assertOk();
        $this->assertSame('ready', $t->json('status'));
        $this->assertStringStartsWith('T: ', $t->json('content.explanation'));

        $a = $this->api($farmer)->postJson("/api/scans/{$d->id}/audio", ['lang' => 'yo'])->assertOk();
        $this->assertSame('unavailable', $a->json('status'));
        $this->assertTrue($a->json('textOnly'));
        $this->api($farmer)->getJson("/api/scans/{$d->id}/audio/yo")->assertOk()->assertJsonPath('textOnly', true);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/synthesize'));
    }

    public function test_the_web_panel_hides_listen_for_a_text_only_language(): void
    {
        SpeechLanguage::where('code', 'yo')->update(['status' => 'live']);
        $farmer = User::factory()->create(['role' => 'farmer']);
        $this->scan($farmer);
        $html = $this->actingAs($farmer)->get(route('diagnostics.history'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="yo"[^>]*data-voice="0"/', $html);
        $this->assertMatchesRegularExpression('/<option value="ha"[^>]*data-voice="1"|<option value="en"[^>]*data-voice="1"/', $html);
        $this->assertStringContainsString('data-listen-show="1"', $html);
    }

    public function test_admin_switches_audio_per_language(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ig = SpeechLanguage::findCode('ig');
        $form = ['name_english' => 'Igbo', 'name_native' => 'Igbo', 'status' => 'in_review', 'rate' => 0.9,
                 'provider' => 'openbible', 'voice' => 'openbible_igbo'];
        $this->actingAs($admin)->patch(route('admin.speech.languages.update', $ig), $form + ['audio_enabled' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($ig->fresh()->hasAudio());
        $this->actingAs($admin)->patch(route('admin.speech.languages.update', $ig), $form + ['audio_enabled' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($ig->fresh()->hasAudio());
        $this->actingAs($admin)->get(route('admin.speech.index'))->assertOk()->assertSee('data-audio-toggle="ig"', false);
    }

    public function test_pregeneration_is_on_queued_after_the_scan_preferred_language_only_and_only_with_audio(): void
    {
        $this->assertTrue(config('speech.pregenerate'), 'on by default since 2026-10-18');
        SpeechLanguage::whereIn('code', ['ha', 'yo'])->update(['status' => 'live']);
        config(['speech.queue_connection' => 'database']);
        Queue::fake();

        $ha = User::factory()->create(['role' => 'farmer', 'preferred_language' => 'ha', 'language' => 'yo']);
        $d = $this->scan($ha);
        Queue::assertPushed(\App\Jobs\TranslateScanResult::class, fn ($j) => $j->diagnosisId === $d->id && $j->lang === 'ha');
        Queue::assertPushed(\App\Jobs\TranslateScanResult::class, 1);

        $yo = User::factory()->create(['role' => 'farmer', 'preferred_language' => 'yo']);
        $this->scan($yo);
        $none = User::factory()->create(['role' => 'farmer']);
        $this->scan($none);
        Queue::assertPushed(\App\Jobs\TranslateScanResult::class, 1); // text-only and unset languages: nothing queued
        Http::assertNothingSent(); // nothing translated or synthesised inside the scan
    }

    public function test_reviewers_are_invited_through_the_app_and_no_password_is_ever_shown(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $res = $this->actingAs($admin)->post(route('admin.speech.reviewers.invite'), [
            'first_name' => 'Hauwa', 'last_name' => 'Sani', 'email' => 'Hauwa.Reviewer@example.org', 'lang' => 'ha', 'kind' => 'native',
        ])->assertRedirect()->assertSessionHas('success');

        $u = User::where('email', 'hauwa.reviewer@example.org')->firstOrFail();
        $this->assertSame('general-user', $u->role);
        $this->assertTrue((bool) $u->force_password_reset);
        $this->assertSame('native', SpeechReviewer::where('user_id', $u->id)->where('lang', 'ha')->value('kind'));

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ReviewerInvitationMail::class, function ($m) use ($u) {
            $html = $m->render();
            return $m->hasTo($u->email) && $m->setPasswordLink !== null
                && str_contains($m->setPasswordLink, 'signature=') && str_contains($html, 'Set my password')
                && !preg_match('/password\s*[:=]/i', strip_tags($html));
        });
        $this->assertStringNotContainsString('password:', strtolower((string) session('success')));

        // An existing account is assigned and told; its password is untouched.
        $existing = User::factory()->create(['role' => 'extension-officer', 'password' => \Illuminate\Support\Facades\Hash::make('their-own-password-1')]);
        $this->actingAs($admin)->post(route('admin.speech.reviewers.invite'), [
            'first_name' => 'A', 'last_name' => 'B', 'email' => $existing->email, 'lang' => 'ha', 'kind' => 'expert',
        ])->assertSessionHas('success');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('their-own-password-1', $existing->fresh()->password));
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ReviewerInvitationMail::class, fn ($m) => $m->hasTo($existing->email) && $m->setPasswordLink === null);

        // Only admins can invite.
        $this->actingAs(User::factory()->create(['role' => 'farmer']))->post(route('admin.speech.reviewers.invite'), [
            'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.org', 'lang' => 'ha', 'kind' => 'native',
        ]);
        $this->assertNull(User::where('email', 'x@example.org')->first());
    }

    // ── go-live: packs of 25, two approved packs ─────────────────────────────

    public function test_packs_are_25_results_spread_over_the_four_scan_types(): void
    {
        $this->assertSame(['crop' => 7, 'livestock' => 6, 'soil' => 6, 'pest' => 6], ReviewPackBuilder::quotasFor(25));
        $this->assertSame(25, config('speech.go_live.pack_size'));
        $this->assertSame(2, config('speech.go_live.min_approved_packs'));
        $this->assertSame(50, config('speech.go_live.min_reviewed_results'));
    }

    /** A pack of $n scan results per type, reviewed by both kinds with the given scores. */
    private function reviewedPack(string $lang, int $perType, int $accuracy, int $naturalness, int $dosage = 0): SpeechReviewPack
    {
        $lang_ = SpeechLanguage::findCode($lang);
        $pack = SpeechReviewPack::create(['lang' => $lang, 'status' => 'open', 'title' => 't',
            'candidates' => [['provider' => $lang_->provider, 'voice' => $lang_->voice, 'license' => 'x']]]);
        $users = [];
        foreach (['native', 'expert'] as $kind) {
            $u = User::factory()->create();
            SpeechReviewer::create(['user_id' => $u->id, 'lang' => $lang, 'kind' => $kind]);
            $users[$kind] = $u;
        }
        $pos = 0;
        foreach (['crop', 'livestock', 'soil', 'pest'] as $type) {
            for ($i = 0; $i < $perType; $i++) {
                $item = SpeechReviewItem::create(['pack_id' => $pack->id, 'position' => ++$pos, 'kind' => 'scan', 'scan_type' => $type,
                    'source_lang' => 'en', 'source_json' => [], 'translation_json' => [], 'audio' => [], 'label_map' => ['A' => 0]]);
                foreach ($users as $kind => $u) {
                    SpeechReview::create(['item_id' => $item->id, 'user_id' => $u->id, 'reviewer_kind' => $kind, 'accuracy' => $accuracy,
                        'voice_ratings' => ['A' => ['naturalness' => $naturalness, 'pronunciation' => 4, 'speed' => 3]],
                        'dosage_errors' => $dosage && $pos === 1 && $kind === 'expert' ? 1 : 0, 'errors' => []]);
                }
            }
        }
        return $pack;
    }

    private function check(array $rule, string $key): ?array
    {
        return collect($rule['checks'])->firstWhere('key', $key);
    }

    public function test_go_live_needs_two_approved_packs(): void
    {
        $this->reviewedPack('ha', 7, 5, 5); // 28 results
        $rule = GoLiveRule::evaluate('ha');
        $this->assertFalse($rule['passes']);
        $this->assertSame(1, $this->check($rule, 'packs')['actual']);

        $this->reviewedPack('ha', 6, 4, 4); // 24 more: 52 results, 2 approved packs
        $rule = GoLiveRule::evaluate('ha');
        $this->assertTrue($rule['passes'], GoLiveRule::failures($rule));
        $this->assertSame(2, $this->check($rule, 'packs')['actual']);
    }

    public function test_a_pack_with_a_dose_error_or_low_scores_is_not_approved(): void
    {
        $this->reviewedPack('ha', 7, 5, 5);
        $this->reviewedPack('ha', 6, 5, 5, dosage: 1);
        $this->reviewedPack('ha', 6, 3, 5);
        $rule = GoLiveRule::evaluate('ha');
        $this->assertSame(1, $this->check($rule, 'packs')['actual']);
        $this->assertFalse($rule['passes']);
    }

    public function test_a_text_only_language_goes_live_on_its_translation_without_a_voice_score(): void
    {
        $this->reviewedPack('yo', 7, 5, 1); // naturalness 1 must not matter: no audio for Yoruba
        $this->reviewedPack('yo', 6, 4, 1);
        $rule = GoLiveRule::evaluate('yo');
        $this->assertNull($this->check($rule, 'naturalness'));
        $this->assertTrue($rule['passes'], GoLiveRule::failures($rule));

        // The same scores fail Hausa, which has audio.
        $this->reviewedPack('ha', 7, 5, 1);
        $this->reviewedPack('ha', 6, 4, 1);
        $this->assertFalse(GoLiveRule::evaluate('ha')['passes']);
    }
}
