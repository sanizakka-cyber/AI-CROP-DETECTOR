<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\ScanError;
use App\Models\User;
use App\Services\SubscriptionLimitService;
use App\Support\Scan\ScanErrorCode as C;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Scan error codes: every way a scan can fail, on the web form (XHR) and the
 * app API. For each code: the farmer's plain message and reference, the
 * staff-only technical details, that nothing was counted or stored, and
 * that no secret, URL, key name or raw provider message reaches a farmer.
 */
class ScanErrorCodesTest extends TestCase
{
    use RefreshDatabase;

    private const ENGINE = 'http://engine.test';
    private const ENGINE_KEY = 'engine-key-should-never-leak-1234567890';
    // Built by concatenation so secret scanners do not flag this test fixture.
    private const CLAUDE_KEY = 'sk-' . 'ant-api03-THISISAFAKEKEYTHATMUSTBEMASKED0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        config(['services.ai_engine.url' => self::ENGINE, 'services.ai_engine.key' => self::ENGINE_KEY]);
    }

    private function jpeg(): UploadedFile
    {
        return UploadedFile::fake()->image('leaf.jpg', 40, 40);
    }

    /** One scan through the web form (XHR) or the app API. */
    private function scan(string $channel, ?User $user, array $override = [])
    {
        if ($channel === 'web') {
            $req = $user ? $this->actingAs($user) : $this;
            return $req->postJson(route('diagnostics.analyze'), array_merge(['scan_type' => 'plant', 'image' => $this->jpeg()], $override));
        }
        $headers = $user ? ['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken] : [];
        return $this->withHeaders($headers + ['Accept' => 'application/json'])
            ->post('/api/diagnose/crop', array_merge(['images' => [$this->jpeg()]], $override));
    }

    /** @var mixed what /predict/* answers next (a response or a callable); changeable within a test. */
    private $engineReply = null;
    private bool $engineFaked = false;

    private function engineAnswers($response): void
    {
        $this->engineReply = $response;
        if ($this->engineFaked) {
            return; // a second Http::fake would not replace the first stub
        }
        $this->engineFaked = true;
        Http::fake([
            self::ENGINE . '/health'    => Http::response(['status' => 'ok']),
            self::ENGINE . '/predict/*' => fn ($request) => is_callable($this->engineReply) && !($this->engineReply instanceof \GuzzleHttp\Promise\PromiseInterface)
                ? ($this->engineReply)($request) : $this->engineReply,
        ]);
    }

    /** code => [how to make it fail, expected HTTP status]; `null` setup = default user, engine working. */
    public static function failures(): array
    {
        $engine = fn ($body, int $status) => fn (self $t) => $t->engineAnswers(Http::response($body, $status));
        $provider = fn (string $err, string $msg) => $engine(['detail' => ['error' => $err, 'message' => $msg . ' key ' . self::CLAUDE_KEY]], 503);
        return [
            C::ENGINE_UNREACHABLE      => [fn (self $t) => $t->engineAnswers(fn () => throw new ConnectionException('cURL error 28: Operation timed out connecting to ' . self::ENGINE)), 503],
            C::ENGINE_AUTH_FAILED      => [$engine(['detail' => 'Unauthorized.'], 401), 503],
            C::AI_PROVIDER_AUTH_FAILED => [$provider('provider_auth_failed', 'Claude API error (AuthenticationError, HTTP 401): invalid x-api-key'), 503],
            C::AI_PROVIDER_NO_CREDIT   => [$provider('provider_no_credit', 'Claude API error (BadRequestError, HTTP 400): Your credit balance is too low'), 503],
            C::AI_PROVIDER_RATE_LIMIT  => [$provider('provider_rate_limit', 'Claude API error (RateLimitError, HTTP 429): rate_limit_error'), 503],
            C::AI_PROVIDER_ERROR       => [$provider('provider_error', 'Claude API error (InternalServerError, HTTP 500): overloaded'), 503],
            C::UPLOAD_BAD_FORMAT       => [$engine(['detail' => ['accepted' => false, 'error' => 'unreadable_image', 'message' => 'This file could not be read as an image.']], 422), 422],
            C::INTERNAL_ERROR          => [function (self $t) {
                $t->engineAnswers(Http::response(['assessment_outcome' => 'assessed', 'disease' => 'Late blight', 'confidence' => 80, 'confidence_valid' => true]));
                Diagnosis::creating(fn () => throw new \RuntimeException('database exploded with password=hunter2 and ' . self::CLAUDE_KEY));
            }, 500],
        ];
    }

    public static function channelsAndFailures(): array
    {
        $out = [];
        foreach (self::failures() as $code => [$setup, $status]) {
            foreach (['web', 'api'] as $ch) {
                $out["{$code} via {$ch}"] = [$ch, $code, $setup, $status];
            }
        }
        return $out;
    }

    private function assertNoSecrets(string $body): void
    {
        foreach ([self::ENGINE_KEY, self::CLAUDE_KEY, 'sk-ant-api03', 'hunter2', self::ENGINE, 'Bearer ', 'vendor/', 'Exception', 'Stack trace'] as $leak) {
            $this->assertStringNotContainsString($leak, $body, "Response leaked: {$leak}");
        }
    }

    #[DataProvider('channelsAndFailures')]
    public function test_each_failure_gets_its_code_a_plain_message_a_reference_and_no_count(string $channel, string $code, \Closure $setup, int $status): void
    {
        $setup($this);
        $farmer = User::factory()->create(['role' => 'farmer']);
        $res = $this->scan($channel, $farmer);

        $res->assertStatus($status)->assertJsonPath('error_code', $code)->assertJsonPath('scan_counted', false);
        $this->assertMatchesRegularExpression('/^SCN-[2-9A-HJ-NP-Z]{5}$/', $res->json('reference'));
        $this->assertSame(__(C::meta($code)['message']), $res->json('error'));
        $this->assertSame('Reference: ' . $res->json('reference'), $res->json('reference_label'));
        $this->assertArrayNotHasKey('technical', $res->json(), 'Farmers never get technical details.');
        $this->assertNoSecrets($res->getContent());

        // Nothing stored or counted; the failure is recorded with its reference.
        $this->assertSame(0, Diagnosis::count());
        $this->assertSame(0, app(SubscriptionLimitService::class)->canScan($farmer)['used']);
        $row = ScanError::where('reference', $res->json('reference'))->sole();
        $this->assertSame($code, $row->code);
        $this->assertSame($channel, $row->channel);
        $this->assertSame($farmer->id, $row->user_id);
        $this->assertStringNotContainsString(self::CLAUDE_KEY, (string) $row->detail);
        $this->assertStringNotContainsString('hunter2', (string) $row->detail);
        $this->assertSame([], array_merge(Storage::disk('local')->allFiles('diagnostics'), Storage::disk('public')->allFiles('diagnoses')),
            'The photo of a failed scan is not kept.');
    }

    #[DataProvider('channelsAndFailures')]
    public function test_staff_see_technical_details_with_secrets_masked(string $channel, string $code, \Closure $setup, int $status): void
    {
        $setup($this);
        $admin = User::factory()->create(['role' => 'admin']);
        $res = $this->scan($channel, $admin);

        $res->assertStatus($status)->assertJsonPath('technical.code', $code)->assertJsonPath('technical.reference', $res->json('reference'));
        $tech = $res->json('technical');
        $this->assertContains($tech['service'], ['website', 'ai_engine', 'claude_api']);
        $this->assertNotEmpty($tech['message']);
        $this->assertNotEmpty($tech['timestamp']);
        $this->assertStringNotContainsString(self::CLAUDE_KEY, $res->getContent());
        $this->assertStringNotContainsString(self::ENGINE_KEY, $res->getContent());
        $this->assertStringNotContainsString('hunter2', $res->getContent());
        if (str_starts_with($code, 'AI_PROVIDER_')) {
            $this->assertSame('claude_api', $tech['service']);
            $this->assertStringContainsString('Claude API error', $tech['message']); // the real cause, masked
        }
        if (str_starts_with($code, 'ENGINE_')) {
            $this->assertSame('ai_engine', $tech['service']);
        }
    }

    public static function channels(): array
    {
        return ['web' => ['web'], 'api' => ['api']];
    }

    #[DataProvider('channels')]
    public function test_quota_exceeded_says_how_many_and_until_when(string $channel): void
    {
        $this->engineAnswers(Http::response(['assessment_outcome' => 'assessed', 'disease' => 'X', 'confidence' => 80, 'confidence_valid' => true]));
        $farmer = User::factory()->create(['role' => 'farmer']);
        for ($i = 0; $i < 5; $i++) {
            Diagnosis::create(['user_id' => $farmer->id, 'type' => 'plant', 'disease_name' => 'x', 'status' => 'reviewed', 'confidence_score' => 80]);
        }
        $res = $this->scan($channel, $farmer)->assertStatus(403)->assertJsonPath('error_code', C::SCAN_QUOTA_EXCEEDED)->assertJsonPath('action', 'upgrade');
        $next = now()->startOfMonth()->addMonth()->isoFormat('D MMMM YYYY');
        $this->assertSame("You have used all 5 scans this month. Upgrade your plan or wait until {$next}.", $res->json('error'));
        $this->assertSame(route('subscription.plans'), $res->json('action_url'));
        $this->assertSame(5, Diagnosis::count());
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/predict/'));
    }

    #[DataProvider('channels')]
    public function test_session_expired_offers_sign_in(string $channel): void
    {
        $res = $this->scan($channel, null)->assertStatus(401)->assertJsonPath('error_code', C::SESSION_EXPIRED)->assertJsonPath('action', 'sign_in');
        $this->assertSame('Your login has expired. Please sign in again.', $res->json('error'));
        $this->assertSame(route('login'), $res->json('action_url'));
        $this->assertNotNull(ScanError::where('code', C::SESSION_EXPIRED)->first());
    }

    #[DataProvider('channels')]
    public function test_upload_problems_are_named(string $channel): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $field = $channel === 'web' ? 'image' : 'images';
        $wrap = fn ($f) => $channel === 'web' ? $f : [$f];

        $big = UploadedFile::fake()->create('leaf.jpg', 10500, 'image/jpeg');
        $this->scan($channel, $farmer, [$field => $wrap($big)])->assertStatus(413)->assertJsonPath('error_code', C::UPLOAD_TOO_LARGE)->assertJsonPath('action', 'choose_photo');

        $txt = UploadedFile::fake()->create('notes.txt', 5, 'text/plain');
        $this->scan($channel, $farmer, [$field => $wrap($txt)])->assertStatus(422)->assertJsonPath('error_code', C::UPLOAD_BAD_FORMAT);

        $tmp = tempnam(sys_get_temp_dir(), 'part');
        file_put_contents($tmp, "\xFF\xD8\xFF");
        $partial = new UploadedFile($tmp, 'leaf.jpg', 'image/jpeg', UPLOAD_ERR_PARTIAL, true);
        $this->scan($channel, $farmer, [$field => $wrap($partial)])->assertStatus(422)->assertJsonPath('error_code', C::UPLOAD_INTERRUPTED)
            ->assertJsonPath('error', 'The photo did not finish uploading. Check your connection and try again.');

        $this->assertSame(0, Diagnosis::count());
    }

    public function test_validation_failed_names_the_field(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $farmer->createToken('t')->plainTextToken, 'Accept' => 'application/json'])
            ->post('/api/diagnose/livestock', ['images' => [$this->jpeg()]])
            ->assertStatus(422)->assertJsonPath('error_code', C::VALIDATION_FAILED)->assertJsonPath('field', 'animalType');
        $this->assertSame('Please check this part of the form: the animal type.', $res->json('error'));
        $this->assertArrayHasKey('animalType', $res->json('details'));

        $this->actingAs($farmer)->postJson(route('diagnostics.analyze'), ['image' => $this->jpeg()])
            ->assertStatus(422)->assertJsonPath('error_code', C::VALIDATION_FAILED)
            ->assertJsonPath('error', 'Please check this part of the form: what you are scanning.');
    }

    public function test_too_many_scans_asks_to_wait(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($farmer)->postJson(route('diagnostics.analyze'), ['scan_type' => 'plant']);
        }
        $this->actingAs($farmer)->postJson(route('diagnostics.analyze'), ['scan_type' => 'plant'])
            ->assertStatus(429)->assertJsonPath('error_code', C::TOO_MANY_SCANS)->assertHeader('Retry-After', '60');
    }

    #[DataProvider('channels')]
    public function test_image_unusable_only_when_the_ai_judged_the_photo(string $channel): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        // The AI ran and judged it: a stored "retake the photo" result.
        $this->engineAnswers(Http::response(['contract_version' => '2026-10-phase11', 'assessment_outcome' => 'poor_image', 'subject_present' => true,
            'image_quality' => 'poor', 'image_issues' => ['blurred'], 'photo_tips' => ['Hold the phone steady'], 'alternative_findings' => [],
            'disease' => null, 'confidence' => null, 'confidence_valid' => true, 'ai_model' => 'claude-sonnet-5']));
        $this->scan($channel, $farmer)->assertOk();
        $d = Diagnosis::sole();
        $this->assertSame('poor_image', $d->assessment_outcome);
        $this->assertSame('Photo too unclear to assess', $d->disease_name);
        $this->assertNull($d->urgency_level);

        // An engine key mismatch is never "Photo too unclear".
        $this->engineAnswers(Http::response(['detail' => 'Unauthorized.'], 401));
        $this->scan($channel, $farmer)->assertStatus(503)->assertJsonPath('error_code', C::ENGINE_AUTH_FAILED);
        $this->assertSame(1, Diagnosis::count());
    }

    public function test_the_engine_not_configured_is_engine_unreachable(): void
    {
        config(['services.ai_engine.url' => '']);
        $this->scan('web', User::factory()->create(['role' => 'farmer']))->assertStatus(503)->assertJsonPath('error_code', C::ENGINE_UNREACHABLE);
    }

    public function test_repeated_engine_failures_email_the_owner_once(): void
    {
        Mail::fake();
        config(['scan_errors.alert_emails' => ['owner@example.org']]);
        $this->engineAnswers(Http::response(['detail' => ['error' => 'provider_no_credit', 'message' => 'credit balance too low']], 503));
        $farmer = User::factory()->create(['role' => 'farmer']);
        for ($i = 0; $i < 4; $i++) {
            $this->scan('api', $farmer);
        }
        Mail::assertNothingOutgoing();
        $this->scan('api', $farmer);
        $this->scan('api', $farmer);
        $this->assertSame(6, ScanError::where('code', C::AI_PROVIDER_NO_CREDIT)->count());
        // One email for the burst, not one per failure.
        Mail::assertSent(\App\Mail\ScanFailureAlertMail::class, 1);
        Mail::assertSent(\App\Mail\ScanFailureAlertMail::class, fn ($m) => $m->hasTo('owner@example.org') && str_contains($m->body, 'AI_PROVIDER_NO_CREDIT'));
    }

    public function test_quota_and_validation_failures_do_not_page_the_owner(): void
    {
        Mail::fake();
        config(['scan_errors.alert_emails' => ['owner@example.org']]);
        $farmer = User::factory()->create(['role' => 'farmer']);
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($farmer)->postJson(route('diagnostics.analyze'), ['image' => $this->jpeg()]);
        }
        Mail::assertNothingOutgoing();
    }

    public function test_admin_error_page_lists_filters_and_counts(): void
    {
        $this->engineAnswers(Http::response(['detail' => 'Unauthorized.'], 401));
        $farmer = User::factory()->create(['role' => 'farmer', 'first_name' => 'Aisha']);
        $ref = $this->scan('api', $farmer)->json('reference');
        config(['services.ai_engine.url' => '']);
        $this->scan('web', $farmer);

        $admin = User::factory()->create(['role' => 'admin']);
        $html = $this->actingAs($admin)->get(route('admin.scan-errors.index'))->assertOk()->getContent();
        $this->assertStringContainsString($ref, $html);
        $this->assertStringContainsString('ENGINE_AUTH_FAILED', $html);
        $this->assertStringContainsString('ENGINE_UNREACHABLE', $html);
        $this->assertStringContainsString('data-per-hour="1"', $html);
        $this->assertStringContainsString('Aisha', $html);

        $filtered = $this->actingAs($admin)->get(route('admin.scan-errors.index', ['code' => 'ENGINE_UNREACHABLE']))->getContent();
        $this->assertStringNotContainsString('data-scan-error="' . $ref . '"', $filtered);

        $this->actingAs(User::factory()->create(['role' => 'customer-support']))->get(route('admin.scan-errors.index'))->assertOk();
        $this->actingAs($farmer)->get(route('admin.scan-errors.index'))->assertRedirect();
    }

    public function test_old_failed_records_show_no_urgency_diagnosis_or_narration(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $d = Diagnosis::create(array_merge(\App\Services\DiagnosisResultMapper::aiUnavailableFallback(), [
            'user_id' => $farmer->id, 'type' => 'plant', 'urgency_level' => 'Medium',
        ]));
        $html = $this->actingAs($farmer)->get(route('diagnostics.history'))->assertOk()->getContent();
        $this->assertStringContainsString('data-failed-scan="1"', $html);
        $this->assertStringContainsString('data-no-diagnosis="1"', $html);
        $this->assertStringNotContainsString('data-urgency=', $html);
        $this->assertStringNotContainsString('tts-' . $d->id . '-playbtn', $html);
        $this->assertSame('', $d->narrationText());

        cache()->forget("diagnosis:v3:{$d->id}");
        $api = $this->withHeaders(['Authorization' => 'Bearer ' . $farmer->createToken('t')->plainTextToken])->getJson("/api/diagnose/{$d->id}");
        $api->assertJsonPath('diagnosis.assessment.failedScan', true)->assertJsonPath('diagnosis.aiResult.urgency', null)->assertJsonPath('diagnosis.aiResult.severity', null);
        $this->assertSame(0, app(SubscriptionLimitService::class)->canScan($farmer)['used']);
    }

    public function test_every_code_has_a_message_in_every_web_language(): void
    {
        foreach (['en', 'ha', 'yo', 'ig', 'fr', 'ar', 'ff'] as $lang) {
            $strings = json_decode(file_get_contents(lang_path("{$lang}.json")), true);
            foreach (C::META as $code => $meta) {
                $this->assertNotEmpty($strings[$meta['message']] ?? null, "{$lang}.json lacks the {$code} message");
            }
            $this->assertNotEmpty($strings['Reference: :ref'] ?? null, "{$lang}.json lacks the reference label");
        }
    }

    public function test_app_users_get_messages_in_their_saved_language(): void
    {
        $this->engineAnswers(Http::response(['detail' => ['error' => 'provider_error', 'message' => 'x']], 503));
        $farmer = User::factory()->create(['role' => 'farmer', 'language' => 'ha']);
        $res = $this->scan('api', $farmer)->assertStatus(503);
        $ha = json_decode(file_get_contents(lang_path('ha.json')), true);
        $this->assertSame($ha[C::meta(C::AI_PROVIDER_ERROR)['message']], $res->json('error'));
    }
}
