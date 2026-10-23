<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\ScanError;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Location capture brief, sections 1-6: accuracy ratings, Android's mock
 * flag (stored, warned, kept out of outbreak maps), a pin moved on the map
 * ("GPS captured, then adjusted by the user", the device's fix kept), the
 * source of every place-name field, and privacy: the exact point only for
 * the farmer and permitted staff; reports, exports and logs at State/LGA.
 */
class LocationAccuracyPrivacyTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\FakesAiEngine;

    // Funtua town, Katsina; and Kano city (Nasarawa LGA, Kano).
    private const FUNTUA = ['11.5230000', '7.3120000'];
    private const KANO_CITY = ['12.0022000', '8.5920000'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->fakeAiEngine();
    }

    private function jpeg(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('leaf.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00fake-image-bytes");
    }

    private function scan(User $u, array $fields): CollectionLocation
    {
        $this->actingAs($u)->post(route('diagnostics.analyze'), ['scan_type' => 'plant', 'image' => $this->jpeg()] + $fields)
            ->assertRedirect(route('diagnostics.history'));
        return CollectionLocation::latest('id')->firstOrFail();
    }

    private function gps(array $over = []): array
    {
        return array_merge([
            'loc_latitude' => self::FUNTUA[0], 'loc_longitude' => self::FUNTUA[1], 'loc_accuracy_meters' => '7',
            'loc_capture_method' => 'gps_device', 'loc_location_source' => 'gps', 'loc_gps_captured_at' => '2026-10-23T07:00:00Z',
        ], $over);
    }

    public static function ratings(): array
    {
        return [[3.0, 'excellent'], [5.0, 'excellent'], [5.1, 'good'], [15.0, 'good'], [15.5, 'fair'], [50.0, 'fair'], [51.0, 'low'], [900.0, 'low'], [null, null]];
    }

    #[DataProvider('ratings')]
    public function test_accuracy_rating_bands(?float $meters, ?string $rating): void
    {
        $this->assertSame($rating, CollectionLocation::accuracyRating($meters));
    }

    public function test_the_reported_accuracy_and_its_rating_are_stored_and_shown(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $loc = $this->scan($u, $this->gps(['loc_accuracy_meters' => '22.4']));
        $this->assertSame(22.4, $loc->accuracy_meters);
        $this->assertSame('fair', $loc->accuracy_rating);

        $html = $this->actingAs($u)->get(route('diagnostics.history'))->getContent();
        $this->assertStringContainsString('data-accuracy-rating="fair"', $html);
        $this->assertStringContainsString('±22 m · Fair', html_entity_decode($html));

        $d = Diagnosis::firstOrFail();
        $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken])
            ->getJson("/api/diagnose/{$d->id}")
            ->assertJsonPath('diagnosis.collectionLocation.accuracyMeters', 22.4)
            ->assertJsonPath('diagnosis.collectionLocation.accuracyRating', 'fair');
    }

    public function test_a_mocked_location_is_stored_flagged_and_left_out_of_outbreak_maps(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $mocked = $this->scan($u, $this->gps(['loc_is_mocked' => '1']));
        $this->assertTrue($mocked->is_mocked);
        $real = $this->scan($u, $this->gps(['loc_is_mocked' => '0']));
        $this->assertFalse($real->is_mocked);
        $this->assertNull($this->scan($u, $this->gps())->is_mocked, 'not reported by the client: unknown, not false');

        $this->assertStringContainsString('data-mocked="1"', $this->actingAs($u)->get(route('diagnostics.history'))->getContent());

        // Outbreaks: the same disease three times, one of them from a mocked location.
        Diagnosis::query()->update(['disease_name' => 'Late blight', 'assessment_outcome' => 'assessed', 'confidence_decision' => 'sufficient']);
        $ceo = User::factory()->create(['role' => 'ceo']);
        $outbreaks = $this->withHeaders(['Authorization' => 'Bearer ' . $ceo->createToken('t')->plainTextToken])
            ->getJson('/api/analytics/outbreaks')->assertOk()->json('outbreaks');
        $blight = collect($outbreaks)->firstWhere('disease', 'Late blight');
        $this->assertSame(2, $blight['cases']);
    }

    public function test_a_moved_pin_is_stored_as_adjusted_with_the_device_fix_kept(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $loc = $this->scan($u, $this->gps([
            'loc_latitude' => self::KANO_CITY[0], 'loc_longitude' => self::KANO_CITY[1], 'loc_location_source' => 'gps_adjusted',
            'loc_gps_original_latitude' => self::FUNTUA[0], 'loc_gps_original_longitude' => self::FUNTUA[1],
        ]));
        $this->assertSame('gps_adjusted', $loc->location_source);
        $this->assertSame(12.0022, $loc->latitude);
        $this->assertSame(11.523, $loc->gps_original_latitude);
        $this->assertSame('GPS captured, then adjusted by the user', $loc->provenanceLabel);
        // Names come from the point the farmer chose, not the device's fix.
        $this->assertSame(['Kano', 'Nasarawa'], [$loc->state, $loc->lga]);
    }

    public function test_adjusted_without_the_device_fix_is_stored_as_plain_gps(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $loc = $this->scan($u, $this->gps(['loc_location_source' => 'gps_adjusted']));
        $this->assertSame('gps', $loc->location_source);
        $this->assertNull($loc->gps_original_latitude);
    }

    public function test_every_place_name_field_records_where_it_came_from(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $loc = $this->scan($u, $this->gps([
            'loc_ward' => 'Tudun Wada', 'loc_community' => 'Funtua town', 'loc_postal_code' => '831101', 'loc_address' => 'Near the market',
        ]));
        $sources = $loc->field_sources;
        $this->assertStringStartsWith('grid3-2022', $sources['state']);
        $this->assertStringStartsWith('grid3-2022', $sources['lga']);
        $this->assertSame('user', $sources['ward']);
        $this->assertSame('user', $sources['community']);
        $this->assertSame('user', $sources['postal_code']);
        $this->assertSame('user', $sources['address_landmark']);
    }

    public function test_who_may_see_the_exact_point(): void
    {
        $owner = User::factory()->create(['role' => 'farmer']);
        $d = Diagnosis::create(['user_id' => $owner->id, 'type' => 'plant', 'disease_name' => 'x', 'confidence_score' => 80, 'status' => 'reviewed']);
        $this->assertTrue(CollectionLocation::canSeeExactPoint($owner, $d));
        $this->assertTrue(CollectionLocation::canSeeExactPoint(User::factory()->create(['role' => 'admin']), $d));
        $this->assertTrue(CollectionLocation::canSeeExactPoint(User::factory()->create(['role' => 'ceo']), $d));
        foreach (['farmer', 'vet', 'agronomist', 'data-analyst', 'research-institution'] as $role) {
            $this->assertFalse(CollectionLocation::canSeeExactPoint(User::factory()->create(['role' => $role]), $d), $role);
        }
        $this->assertFalse(CollectionLocation::canSeeExactPoint(null, $d));
        config(['location_privacy.exact_point_roles' => []]);
        $this->assertFalse(CollectionLocation::canSeeExactPoint(User::factory()->create(['role' => 'admin']), $d));
    }

    public function test_the_owner_sees_the_point_in_the_app_and_nobody_else_can_open_the_scan(): void
    {
        $owner = User::factory()->create(['role' => 'farmer']);
        $this->scan($owner, $this->gps());
        $d = Diagnosis::firstOrFail();
        $this->withHeaders(['Authorization' => 'Bearer ' . $owner->createToken('t')->plainTextToken])
            ->getJson("/api/diagnose/{$d->id}")
            ->assertJsonPath('diagnosis.collectionLocation.latitude', 11.523);

        $other = User::factory()->create(['role' => 'farmer']);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $other->createToken('t')->plainTextToken])->getJson("/api/diagnose/{$d->id}");
        $this->assertContains($res->status(), [403, 404]);
        $this->assertStringNotContainsString('11.523', $res->getContent());
    }

    public function test_the_pdf_report_has_state_and_lga_only(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->scan($u, $this->gps(['loc_community' => 'Funtua town', 'loc_address' => 'Behind the mosque', 'loc_postal_code' => '831101']));
        $pdf = $this->actingAs($u)->get(route('diagnostics.report', Diagnosis::firstOrFail()))->assertOk()->getContent();
        $text = html_entity_decode(strip_tags($pdf));
        $this->assertStringContainsString('Katsina', $text);
        $this->assertStringContainsString('Funtua', $text);
        $this->assertStringContainsString('GPS point recorded', $text);
        foreach (['11.523', '7.312', 'Funtua town', 'Behind the mosque', '831101'] as $private) {
            $this->assertStringNotContainsString($private, $text);
        }
    }

    public function test_the_research_export_has_state_and_lga_only(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->scan($u, $this->gps(['loc_community' => 'Funtua town', 'loc_is_mocked' => '1']));
        $ceo = User::factory()->create(['role' => 'ceo']);
        $csv = $this->actingAs($ceo)->get('/ceo/ai-analytics/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('Sample GPS Point Recorded', $csv);
        $this->assertStringNotContainsString('Latitude', $csv);
        $this->assertStringNotContainsString('11.523', $csv);
        $this->assertStringNotContainsString('7.312', $csv);
        $this->assertStringNotContainsString('Funtua town', $csv);
        $row = str_getcsv(collect(explode("\n", trim($csv)))->last());
        $header = str_getcsv(explode("\n", $csv)[0]);
        $cols = array_combine($header, $row);
        $this->assertSame('Funtua', $cols['Sample Collection LGA']);
        $this->assertSame('yes', $cols['Sample GPS Point Recorded']);
        $this->assertSame('good', $cols['Sample GPS Accuracy Rating']);
        $this->assertSame('yes', $cols['Sample GPS Mocked']);
    }

    public function test_a_failed_scan_logs_no_coordinates(): void
    {
        config(['services.ai_engine.url' => '']); // the engine is down: the scan fails and is logged
        Log::spy();
        $u = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($u)->postJson(route('diagnostics.analyze'), ['scan_type' => 'plant', 'image' => $this->jpeg()] + $this->gps())
            ->assertStatus(503);

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context = []) {
            $this->assertStringNotContainsString('11.523', json_encode([$message, $context]));
            return true;
        });
        $error = ScanError::firstOrFail();
        $this->assertStringNotContainsString('11.523', json_encode($error->toArray()));
    }

    public function test_the_scan_page_has_the_capture_tools_privacy_note_and_map_tiles_allowed(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $res = $this->actingAs($u)->get(route('diagnostics.scan'))->assertOk();
        $html = $res->getContent();
        $this->assertStringContainsString('js/gps-quality.js', $html);
        $this->assertStringContainsString('data-loc-progress="1"', $html);
        $this->assertStringContainsString('data-loc-result="1"', $html);
        $this->assertStringContainsString('Enter manually instead', $html);
        $this->assertStringContainsString('Privacy: the exact point is visible only to you and authorised MSAS staff.', html_entity_decode($html));
        $this->assertStringContainsString('https://tile.openstreetmap.org', $res->headers->get('Content-Security-Policy'));
    }

    public static function langs(): array
    {
        return array_map(fn ($l) => [$l], ['en', 'ha', 'yo', 'ig', 'fr', 'ar', 'ff']);
    }

    #[DataProvider('langs')]
    public function test_the_location_strings_are_in_every_web_language(string $lang): void
    {
        $strings = json_decode(file_get_contents(lang_path("{$lang}.json")), true);
        foreach (['Use GPS', 'Enter by hand', 'Enter manually instead', 'Improving accuracy… ±:from m → ±:to m', 'Location captured: ±:acc m (:rating).',
            'Excellent', 'Good', 'Fair', 'Drag the pin if it is not where the sample was collected.', 'This site is not allowed to use your location.',
            'Privacy: the exact point is visible only to you and authorised MSAS staff. Reports, exports, research data and outbreak maps show the State and LGA only.',
            'GPS captured, then adjusted by the user', 'GPS point recorded'] as $key) {
            $this->assertNotEmpty($strings[$key] ?? null, "{$lang}.json is missing \"{$key}\"");
        }
        foreach ([':from', ':to'] as $param) {
            $this->assertStringContainsString($param, $strings['Improving accuracy… ±:from m → ±:to m'], "{$lang}: {$param} lost");
        }
    }
}
