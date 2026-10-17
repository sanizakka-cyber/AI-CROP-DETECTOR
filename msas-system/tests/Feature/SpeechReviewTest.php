<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\SpeechLanguage;
use App\Models\SpeechReview;
use App\Models\SpeechReviewer;
use App\Models\SpeechReviewItem;
use App\Models\SpeechReviewPack;
use App\Models\User;
use App\Services\Speech\GoLiveRule;
use App\Services\Speech\Providers\ProviderRegistry;
use App\Services\Speech\ReviewPackBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Native-speaker review packs (Phase 13.1): building packs from real
 * anonymised scans, blind A/B/C voices, reviewer access, saving reviews,
 * and the configurable go-live rule. Translator and voices are faked.
 */
class SpeechReviewTest extends TestCase
{
    use RefreshDatabase;

    private const ENGINE = 'http://engine.test';
    private const TTS = 'http://tts.test';
    private const OPENBIBLE = 'http://openbible.test';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ai_engine.url' => self::ENGINE,
            'speech.self_hosted.url' => self::TTS,
            'speech.providers.openbible.url' => self::OPENBIBLE,
            'speech.disk' => 'local',
        ]);
        Storage::fake('local');
    }

    private function fakeServices(?callable $reject = null): void
    {
        $tts = fn (HttpRequest $r) => Http::response([
            'format' => 'mp3', 'mime' => 'audio/mpeg', 'audio_base64' => base64_encode("{$r['engine']}:{$r['voice']}|"),
            'duration_sec' => 1.0, 'segments' => array_map(fn ($i) => ['index' => $i, 'start' => 0, 'end' => 0.8], array_keys($r['segments'])),
            'engine' => $r['engine'], 'voice' => $r['voice'], 'license' => 'x', 'latency_ms' => 1,
        ]);
        Http::fake([
            self::ENGINE . '/translate/scan-result' => function (HttpRequest $r) use ($reject) {
                $c = $r['content'];
                $t = [
                    'observations'    => array_map(fn ($s) => "Ƙ: {$s}", $c['observations']),
                    'explanation'     => $c['explanation'] === '' ? '' : "Ƙ: {$c['explanation']}",
                    'recommendations' => array_map(fn ($s) => "Ƙ: {$s}", $c['recommendations']),
                    'safety_note'     => 'Wannan shawarar AI ce kuma tana iya yin kuskure.',
                ];
                if ($reject && $reject($c)) {
                    $t['recommendations'][] = 'Ƙ: Fesa 25 g kowace lita.'; // invented dose: the integrity check rejects it
                }
                return Http::response(['translated' => $t, 'model' => 'fake']);
            },
            self::TTS . '/synthesize' => $tts,
            self::OPENBIBLE . '/synthesize' => $tts,
            '*' => Http::response('unexpected', 500),
        ]);
    }

    private function scan(string $type, array $over = []): Diagnosis
    {
        $f = json_decode(file_get_contents(base_path('../shared-fixtures/canonical-diagnosis.json')), true);
        $owner = User::factory()->create(['role' => 'farmer', 'first_name' => 'Aminu', 'last_name' => 'Bello', 'phone' => '+234803' . random_int(1000000, 9999999)]);
        $d = Diagnosis::create(array_merge($f['record'], ['user_id' => $owner->id, 'language' => 'en', 'type' => $type], $over));
        CollectionLocation::create(['diagnosis_id' => $d->id, 'country' => 'Nigeria', 'state' => 'Katsina', 'lga' => 'Funtua', 'community' => 'Unguwar Rimi']);
        return $d;
    }

    private function buildPack(int $perType = 2): SpeechReviewPack
    {
        foreach (['plant', 'livestock', 'soil', 'pest'] as $t) {
            for ($i = 0; $i < $perType; $i++) {
                $this->scan($t, ['explanation' => "Aminu Bello's farm in Unguwar Rimi shows typical lesions {$t} {$i}"]);
            }
        }
        return app(ReviewPackBuilder::class)->build('ha', ['yarngpt:hausa_female1', 'yarngpt:hausa_male1', 'openbible:openbible_hausa'], $perType);
    }

    private function reviewer(string $kind, string $lang = 'ha'): User
    {
        $u = User::factory()->create(['role' => 'farmer']);
        SpeechReviewer::create(['user_id' => $u->id, 'lang' => $lang, 'kind' => $kind]);
        return $u;
    }

    // ── building packs ───────────────────────────────────────────────────────

    public function test_a_pack_has_anonymised_scans_of_every_type_and_the_extra_items_each_with_every_voice(): void
    {
        $this->fakeServices();
        $pack = $this->buildPack(2);

        $scans = $pack->items->where('kind', 'scan');
        $this->assertCount(8, $scans);
        $this->assertSame(['crop' => 2, 'livestock' => 2, 'pest' => 2, 'soil' => 2], $scans->countBy('scan_type')->sortKeys()->all());
        $this->assertSame(['labels', 'lexicon', 'numbers', 'safety_note'], $pack->items->where('kind', '!=', 'scan')->pluck('kind')->sort()->values()->all());

        foreach ($pack->items as $item) {
            // Every voice made a file, labelled A/B/C with a per-item mapping.
            $this->assertSame(['A', 'B', 'C'], $item->playableLabels());
            $this->assertEqualsCanonicalizing([0, 1, 2], array_values($item->label_map));
            foreach ($item->audio as $a) {
                Storage::disk('local')->assertExists($a['path']);
                $this->assertDoesNotMatchRegularExpression('/yarngpt|openbible|hausa_/', $a['path']); // nothing in the name gives the voice away
            }
        }

        // Personal details and place names are removed from what reviewers see.
        $text = json_encode($scans->pluck('translation_json')->all() + $scans->pluck('source_json')->all(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Aminu', $text);
        $this->assertStringNotContainsString('Unguwar Rimi', $text);
        $this->assertStringContainsString('[name]', $text);
        $this->assertStringContainsString('[place]', $text);

        // The lexicon always includes the required terms; numbers are shown as they will be spoken.
        $lexicon = $pack->items->firstWhere('kind', 'lexicon')->translation_json['rows'];
        $this->assertEqualsCanonicalizing(['MSAS FarmAI', 'GPS', 'LGA'], array_slice(array_column($lexicon, 'source'), 0, 3));
        $numbers = $pack->items->firstWhere('kind', 'numbers')->translation_json['rows'];
        $this->assertSame('Apply 2.5 g per litre of water.', $numbers[0]['source']);
        $days = collect($numbers)->firstWhere('source', 'Repeat after 7 days.')['text'];
        $this->assertStringContainsString('bakwai', $days); // whole numbers are spelled in Hausa
        $this->assertStringContainsString('2.5', $numbers[0]['text']); // decimals stay as digits until reviewers approve rules for them
        // The OpenBible voice went to its own service.
        Http::assertSent(fn ($r) => str_starts_with($r->url(), self::OPENBIBLE) && $r['engine'] === 'openbible');
    }

    public function test_a_scan_whose_translation_fails_the_checks_is_skipped_not_fixed(): void
    {
        $this->fakeServices(fn ($content) => str_contains($content['explanation'] ?? '', 'soil 0'));
        $pack = $this->buildPack(1);
        $this->assertSame(['crop', 'livestock', 'pest'], $pack->items->where('kind', 'scan')->pluck('scan_type')->sort()->values()->all());
    }

    public function test_candidates_must_be_licence_safe_voices_of_the_language(): void
    {
        $b = app(ReviewPackBuilder::class);
        $this->expectException(\InvalidArgumentException::class);
        $b->parseCandidates(['farmerline:farmerline_hausa'], 'ha'); // switched off until Farmerline confirms in writing
    }

    public function test_farmerline_is_switched_off_until_confirmed(): void
    {
        $this->assertArrayNotHasKey('farmerline', ProviderRegistry::selectable());
        config(['speech.providers.farmerline.enabled' => true]);
        $this->assertArrayHasKey('farmerline', ProviderRegistry::selectable());
        $this->assertArrayHasKey('openbible', ProviderRegistry::selectable());
    }

    // ── reviewing ────────────────────────────────────────────────────────────

    public function test_reviewers_see_only_their_languages_and_never_which_voice_is_which(): void
    {
        $this->fakeServices();
        $pack = $this->buildPack(1);
        $item = $pack->items->firstWhere('kind', 'scan');
        $native = $this->reviewer('native');

        $this->actingAs(User::factory()->create(['role' => 'farmer']))->get(route('admin.speech.review.index'))->assertForbidden();
        $this->actingAs($this->reviewer('native', 'yo'))->get(route('admin.speech.review.item', $item))->assertForbidden();

        $html = $this->actingAs($native)->get(route('admin.speech.review.item', $item))->assertOk()->getContent();
        $this->assertStringContainsString('Voice A', $html);
        $this->assertStringContainsString('Voice C', $html);
        $this->assertDoesNotMatchRegularExpression('/yarngpt|openbible|hausa_female1|hausa_male1|YarnGPT|OpenBible/', $html);

        $audio = $this->actingAs($native)->get(route('admin.speech.review.audio', [$item, 'B']))->assertOk();
        $this->assertStringContainsString('voice-B.mp3', (string) $audio->headers->get('Content-Disposition'));
        $this->assertStringContainsString(':', $audio->streamedContent());

        // Results (which unblind the voices) are admin only: the role middleware turns reviewers away.
        $this->assertContains($this->actingAs($native)->get(route('admin.speech.review.results', 'ha'))->status(), [302, 403]);
    }

    public function test_a_review_needs_every_voice_rated_and_records_errors_and_the_preferred_voice(): void
    {
        $this->fakeServices();
        $pack = $this->buildPack(1);
        $item = $pack->items->firstWhere('kind', 'scan');
        $expert = $this->reviewer('expert');
        $rate = fn ($n) => ['naturalness' => $n, 'pronunciation' => $n, 'speed' => 3];

        $this->actingAs($expert)->post(route('admin.speech.review.store', $item), [
            'accuracy' => 4, 'voice_ratings' => ['A' => $rate(4)],
        ])->assertSessionHasErrors('voice_ratings');

        $this->actingAs($expert)->post(route('admin.speech.review.store', $item), [
            'accuracy' => 3,
            'voice_ratings' => ['A' => $rate(4), 'B' => $rate(2), 'C' => $rate(5)],
            'preferred_label' => 'C',
            'errors' => [
                ['section' => 'recommendations', 'quote' => '25 g', 'type' => 'dosage', 'note' => 'should be 2.5 g'],
                ['section' => 'explanation', 'quote' => 'kuskure', 'type' => 'grammar'],
            ],
        ])->assertRedirect();

        $r = SpeechReview::first();
        $this->assertSame('expert', $r->reviewer_kind);
        $this->assertSame(1, $r->dosage_errors);
        $this->assertSame($item->label_map['C'], $r->preferred_candidate);
        $this->assertSame(5, $r->ratingsForCandidate($item->label_map['C'])['naturalness']);
    }

    // ── go-live rule ─────────────────────────────────────────────────────────

    private function reviewAll(SpeechReviewPack $pack, array $users, int $accuracy, int $naturalness, int $dosage = 0): void
    {
        foreach ($pack->items->where('kind', 'scan') as $item) {
            foreach ($users as $u) {
                SpeechReview::create([
                    'item_id' => $item->id, 'user_id' => $u->id, 'reviewer_kind' => SpeechReviewer::where('user_id', $u->id)->value('kind'),
                    'accuracy' => $accuracy,
                    'voice_ratings' => collect($item->label_map)->map(fn () => ['naturalness' => $naturalness, 'pronunciation' => 4, 'speed' => 3])->all(),
                    'dosage_errors' => $dosage, 'errors' => $dosage ? [['quote' => 'x', 'type' => 'dosage']] : [],
                ]);
            }
        }
    }

    public function test_go_live_needs_enough_results_of_every_type_reviewed_by_both_kinds_good_scores_and_no_dose_errors(): void
    {
        $this->fakeServices();
        config(['speech.go_live.min_reviewed_results' => 8, 'speech.go_live.min_per_scan_type' => 2]);
        $pack = $this->buildPack(2);
        SpeechLanguage::where('code', 'ha')->update(['provider' => 'openbible', 'voice' => 'openbible_hausa']);
        $native = $this->reviewer('native');
        $expert = $this->reviewer('expert');

        $this->assertFalse(GoLiveRule::evaluate('ha')['passes']); // nothing reviewed

        $this->reviewAll($pack, [$native], 5, 5);
        $rule = GoLiveRule::evaluate('ha');
        $this->assertFalse($rule['passes']); // the expert has not reviewed yet
        $this->assertSame(0, collect($rule['checks'])->firstWhere('key', 'reviewed')['actual']);

        $this->reviewAll($pack, [$expert], 4, 4);
        $this->assertTrue(GoLiveRule::evaluate('ha')['passes']);

        // Configurable: a stricter accuracy bar fails it.
        config(['speech.go_live.min_accuracy' => 4.6]);
        $this->assertFalse(GoLiveRule::evaluate('ha')['passes']);
        config(['speech.go_live.min_accuracy' => 4.0]);

        // One dose or chemical error anywhere blocks it.
        $extra = User::factory()->create();
        SpeechReviewer::create(['user_id' => $extra->id, 'lang' => 'ha', 'kind' => 'native']);
        SpeechReview::create(['item_id' => $pack->items->firstWhere('kind', 'numbers')->id, 'user_id' => $extra->id, 'reviewer_kind' => 'native',
            'accuracy' => 5, 'dosage_errors' => 1, 'errors' => [['quote' => 'biyu', 'type' => 'dosage']]]);
        $rule = GoLiveRule::evaluate('ha');
        $this->assertFalse($rule['passes']);
        $this->assertSame(1, collect($rule['checks'])->firstWhere('key', 'dosage')['actual']);
    }

    public function test_naturalness_counts_only_the_voice_the_language_will_use(): void
    {
        $this->fakeServices();
        config(['speech.go_live.min_reviewed_results' => 1, 'speech.go_live.min_per_scan_type' => 0]);
        $pack = $this->buildPack(1);
        $this->reviewAll($pack, [$this->reviewer('native'), $this->reviewer('expert')], 5, 5);

        SpeechLanguage::where('code', 'ha')->update(['provider' => 'yarngpt', 'voice' => 'hausa_male2']); // a voice nobody rated
        $rule = GoLiveRule::evaluate('ha');
        $this->assertFalse($rule['passes']);
        $this->assertNull(collect($rule['checks'])->firstWhere('key', 'naturalness')['actual']);

        SpeechLanguage::where('code', 'ha')->update(['voice' => 'hausa_male1']);
        $this->assertTrue(GoLiveRule::evaluate('ha')['passes']);
    }

    public function test_admin_cannot_set_a_language_live_until_the_rule_passes_and_sees_unblinded_results(): void
    {
        $this->fakeServices();
        config(['speech.go_live.min_reviewed_results' => 4, 'speech.go_live.min_per_scan_type' => 1]);
        $pack = $this->buildPack(1);
        $admin = User::factory()->create(['role' => 'admin']);
        $ha = SpeechLanguage::findCode('ha');
        $form = fn () => ['name_english' => 'Hausa', 'name_native' => 'Hausa', 'status' => 'live', 'rate' => 0.9,
            'provider' => 'openbible', 'voice' => 'openbible_hausa'];

        $this->actingAs($admin)->patch(route('admin.speech.languages.update', $ha), $form())->assertSessionHasErrors('status');
        $this->assertSame('in_review', $ha->fresh()->status);

        $this->actingAs($admin)->post(route('admin.speech.reviewers.store'), ['email' => ($n = User::factory()->create())->email, 'lang' => 'ha', 'kind' => 'native'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.speech.reviewers.store'), ['email' => ($e = User::factory()->create())->email, 'lang' => 'ha', 'kind' => 'expert'])->assertSessionHasNoErrors();
        SpeechLanguage::where('code', 'ha')->update(['provider' => 'openbible', 'voice' => 'openbible_hausa']);
        $this->reviewAll($pack, [$n, $e], 5, 5);

        $this->actingAs($admin)->patch(route('admin.speech.languages.update', $ha), $form())->assertSessionHasNoErrors();
        $this->assertSame('live', $ha->fresh()->status);

        $html = $this->actingAs($admin)->get(route('admin.speech.review.results', 'ha'))->assertOk()->getContent();
        $this->assertStringContainsString('openbible: openbible_hausa', $html);
        $this->assertStringContainsString('yarngpt: hausa_female1', $html);
        $this->actingAs($admin)->get(route('admin.speech.index'))->assertOk()->assertSee('Native-speaker review');
    }
}
