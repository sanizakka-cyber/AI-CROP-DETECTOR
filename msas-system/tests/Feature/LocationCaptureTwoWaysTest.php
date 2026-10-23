<?php

namespace Tests\Feature;

use App\Data\NigeriaLocations;
use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use App\Services\LgaLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The two ways to record where a sample was collected: GPS capture (State
 * and LGA filled from the GRID3 boundaries) or manual entry. Covers how
 * each is stored (location_source, field_sources), the scan place
 * kept apart from the collection place, the lookup endpoint on web and
 * API, and the LGA spelling migration.
 */
class LocationCaptureTwoWaysTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\FakesAiEngine;

    // Funtua town, Katsina.
    private const FUNTUA = ['11.5230', '7.3120'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->fakeAiEngine();
    }

    private function scan(array $fields): CollectionLocation
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => UploadedFile::fake()->createWithContent('leaf.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00fake-image-bytes"),
        ] + $fields)->assertRedirect(route('diagnostics.history'));

        return CollectionLocation::firstOrFail();
    }

    public function test_every_boundary_is_named_as_a_dropdown_lga_and_every_lga_has_a_boundary(): void
    {
        $dropdown = [];
        foreach (NigeriaLocations::states() as $s) {
            foreach ($s['lgas'] as $lga) {
                $dropdown["{$s['name']}|{$lga}"] = true;
            }
        }
        $index = json_decode(file_get_contents(resource_path('geo/nga-lgas.json')), true);
        $boundaries = [];
        foreach ($index['features'] as $f) {
            $boundaries["{$f['state']}|{$f['lga']}"] = true;
        }

        $this->assertSame([], array_keys(array_diff_key($boundaries, $dropdown)));
        $this->assertSame([], array_keys(array_diff_key($dropdown, $boundaries)));
    }

    public function test_lookup_finds_known_places_and_nothing_outside_nigeria(): void
    {
        $this->assertSame(['Katsina', 'Funtua'], array_values(array_slice(LgaLookup::locate(11.523, 7.312), 0, 2)));
        $this->assertSame('Maiduguri', LgaLookup::locate(11.8311, 13.1510)['lga']);
        $this->assertSame('Lagos Island', LgaLookup::locate(6.4541, 3.3947)['lga']);
        $this->assertNull(LgaLookup::locate(51.5074, -0.1278));
        $this->assertNull(LgaLookup::locate(null, 7.3));
    }

    public function test_gps_capture_without_names_gets_state_and_lga_from_the_boundaries(): void
    {
        $loc = $this->scan([
            'loc_latitude' => self::FUNTUA[0], 'loc_longitude' => self::FUNTUA[1], 'loc_accuracy_meters' => '9',
            'loc_capture_method' => 'gps_device', 'loc_location_source' => 'gps',
            'loc_gps_captured_at' => '2026-10-22T09:15:00Z',
        ]);

        $this->assertSame('Katsina', $loc->state);
        $this->assertSame('Funtua', $loc->lga);
        $this->assertSame('gps', $loc->location_source);
        $this->assertStringStartsWith('grid3-2022', $loc->field_sources['state']);
        $this->assertSame($loc->field_sources['state'], $loc->field_sources['lga']);
        $this->assertSame('device', $loc->coordinate_source);
        $this->assertSame('2026-10-22 09:15:00', $loc->gps_captured_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_manual_entry_is_stored_as_the_users_names_with_no_coordinates(): void
    {
        $loc = $this->scan([
            'loc_state' => 'Kano', 'loc_lga' => 'Dala', 'loc_community' => 'Kofar Mata',
            'loc_capture_method' => 'administrative_selection', 'loc_location_source' => 'manual',
        ]);

        $this->assertSame('manual', $loc->location_source);
        $this->assertSame(['state' => 'user', 'lga' => 'user', 'community' => 'user'], $loc->field_sources);
        $this->assertSame('Dala', $loc->lga);
        $this->assertNull($loc->latitude);
        $this->assertNull($loc->gps_captured_at);
    }

    public function test_names_claimed_from_the_lookup_are_recorded_as_such_only_when_they_match(): void
    {
        $matching = $this->scan([
            'loc_latitude' => self::FUNTUA[0], 'loc_longitude' => self::FUNTUA[1], 'loc_capture_method' => 'gps_device',
            'loc_state' => 'Katsina', 'loc_lga' => 'Funtua', 'loc_place_names_source' => 'lookup', 'loc_location_source' => 'gps',
        ]);
        $this->assertStringStartsWith('grid3-2022', $matching->field_sources['lga']);

        CollectionLocation::query()->delete();

        $changed = $this->scan([
            'loc_latitude' => self::FUNTUA[0], 'loc_longitude' => self::FUNTUA[1], 'loc_capture_method' => 'gps_device',
            'loc_state' => 'Katsina', 'loc_lga' => 'Bakori', 'loc_place_names_source' => 'lookup', 'loc_location_source' => 'gps_edited',
        ]);
        $this->assertSame('Bakori', $changed->lga);
        $this->assertSame('user', $changed->field_sources['lga']);
        $this->assertSame('gps_edited', $changed->location_source);
        $this->assertSame('GPS captured, then changed by the user', $changed->provenanceLabel);
    }

    public function test_a_point_outside_every_lga_leaves_the_names_blank(): void
    {
        $loc = $this->scan(['loc_latitude' => '51.5074', 'loc_longitude' => '-0.1278', 'loc_capture_method' => 'gps_device']);

        $this->assertNull($loc->state);
        $this->assertNull($loc->lga);
        $this->assertNull($loc->field_sources);
        $this->assertSame('gps', $loc->location_source);
    }

    public function test_scan_place_gps_is_kept_apart_from_the_collection_place(): void
    {
        $loc = $this->scan([
            'loc_state' => 'Katsina', 'loc_lga' => 'Funtua', 'loc_location_source' => 'manual',
            'loc_differs_from_scan' => '1',
            'loc_scan_latitude' => '12.0022', 'loc_scan_longitude' => '8.5920', 'loc_scan_accuracy_meters' => '15',
        ]);

        $this->assertTrue($loc->differs_from_scan_location);
        $this->assertNull($loc->latitude);
        $this->assertSame(12.0022, $loc->scan_latitude);
        $this->assertSame(15.0, $loc->scan_accuracy_meters);
        $this->assertSame('Funtua', $loc->lga);
    }

    public function test_an_older_client_sending_its_own_fix_with_scanning_elsewhere_files_the_fix_as_the_scan_place(): void
    {
        $loc = $this->scan([
            'loc_state' => 'Katsina', 'loc_differs_from_scan' => '1',
            'loc_latitude' => '12.0022', 'loc_longitude' => '8.5920', 'loc_accuracy_meters' => '20',
            'loc_capture_method' => 'gps_device',
        ]);

        $this->assertNull($loc->latitude);
        $this->assertNull($loc->accuracy_meters);
        $this->assertSame(12.0022, $loc->scan_latitude);
        $this->assertSame('administrative_selection', $loc->capture_method);
        $this->assertSame('manual', $loc->location_source);
        // Not filled from the scan place: that is Kano, the sample is from Katsina.
        $this->assertNull($loc->lga);
    }

    public function test_only_a_scan_place_still_records_a_location_row(): void
    {
        $loc = $this->scan(['loc_differs_from_scan' => '1', 'loc_scan_latitude' => '12.0022', 'loc_scan_longitude' => '8.5920']);

        $this->assertNull($loc->state);
        $this->assertSame(8.592, $loc->scan_longitude);
    }

    public function test_unknown_location_source_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => UploadedFile::fake()->createWithContent('leaf.jpg', "\xFF\xD8\xFF\xE0fake"),
            'loc_state' => 'Kano', 'loc_location_source' => 'satellite',
        ])->assertSessionHasErrors('loc_location_source');
    }

    public function test_web_lookup_endpoint(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)->getJson(route('diagnostics.location-lookup', ['lat' => self::FUNTUA[0], 'lon' => self::FUNTUA[1]]))
            ->assertOk()
            ->assertJson(['found' => true, 'state' => 'Katsina', 'lga' => 'Funtua'])
            ->assertJsonPath('attribution', fn ($a) => str_contains($a, 'GRID3') && str_contains($a, 'CC BY 4.0'));

        $this->actingAs($user)->getJson(route('diagnostics.location-lookup', ['lat' => '51.5', 'lon' => '-0.12']))
            ->assertOk()->assertExactJson(['found' => false]);

        $this->actingAs($user)->getJson(route('diagnostics.location-lookup', ['lat' => '11.5']))
            ->assertStatus(422);
    }

    public function test_web_lookup_needs_a_login(): void
    {
        $this->getJson(route('diagnostics.location-lookup', ['lat' => self::FUNTUA[0], 'lon' => self::FUNTUA[1]]))
            ->assertUnauthorized();
    }

    public function test_api_lookup_and_a_mobile_gps_scan_report_the_capture_details(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/health'       => Http::response(['status' => 'ok'], 200),
            '*/predict/crop' => Http::response(['subject_name' => 'Maize', 'disease' => 'Healthy', 'confidence' => 91, 'confidence_valid' => true], 200),
        ]);
        $user = User::factory()->create(['role' => 'farmer']);
        $headers = ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];

        $this->withHeaders($headers)->getJson('/api/locations/lookup?lat=' . self::FUNTUA[0] . '&lon=' . self::FUNTUA[1])
            ->assertOk()->assertJson(['found' => true, 'lga' => 'Funtua']);
        $this->flushHeaders()->getJson('/api/locations/lookup?lat=11.5&lon=7.3')->assertUnauthorized();

        // An offline capture syncing later: coordinates only, names left to the server.
        $this->withHeaders($headers)->post('/api/diagnose/crop', [
            'images' => [UploadedFile::fake()->image('leaf.jpg')],
            'loc_latitude' => self::FUNTUA[0], 'loc_longitude' => self::FUNTUA[1],
            'loc_capture_method' => 'gps_device', 'loc_location_source' => 'gps',
            'loc_gps_captured_at' => '2026-10-21T07:00:00Z',
            'loc_state' => '', 'loc_lga' => '',
        ])->assertOk();

        $diagnosis = Diagnosis::firstOrFail();
        $this->withHeaders($headers)->getJson("/api/diagnose/{$diagnosis->id}")
            ->assertOk()
            ->assertJsonPath('diagnosis.collectionLocation.lga', 'Funtua')
            ->assertJsonPath('diagnosis.collectionLocation.locationSource', 'gps')
            ->assertJsonPath('diagnosis.collectionLocation.fieldSources.lga', fn ($s) => str_starts_with($s, 'grid3-2022'))
            ->assertJsonPath('diagnosis.collectionLocation.scanLatitude', null);
    }

    public function test_misspelt_lgas_already_saved_are_renamed(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'state' => 'Adamawa', 'lga' => 'Fufure']);
        $elsewhere = User::factory()->create(['role' => 'farmer', 'state' => 'Kano', 'lga' => 'Kumi']);
        $diagnosis = Diagnosis::create(['user_id' => $farmer->id, 'type' => 'plant', 'disease_name' => 'x', 'confidence_score' => 50, 'status' => 'reviewed']);
        DB::table('collection_locations')->insert(['diagnosis_id' => $diagnosis->id, 'country' => 'Nigeria', 'state' => 'Taraba', 'lga' => 'Kumi', 'created_at' => now(), 'updated_at' => now()]);

        $migration = require database_path('migrations/2026_10_23_000001_correct_lga_spellings.php');
        $migration->up();

        $this->assertSame('Fufore', $farmer->fresh()->lga);
        $this->assertSame('Kurmi', CollectionLocation::first()->lga);
        // Same name in a state where it is not the misspelling: untouched.
        $this->assertSame('Kumi', $elsewhere->fresh()->lga);

        $migration->down();
        $this->assertSame('Fufure', $farmer->fresh()->lga);
    }
}
