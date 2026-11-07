<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Community, Address/Landmark and Postal Code suggested from the location
 * (OpenStreetMap via our server, App\Services\ReverseGeocoder): only inside
 * the point's LGA, no invented postal code, nothing from a low-accuracy
 * reading, empty on failure, and recorded as gps (kept) or user (typed).
 * State and LGA keep coming from the GRID3 boundaries.
 */
class LocationSuggestionsTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\FakesAiEngine;

    // Funtua town, Katsina (Funtua LGA).
    private const LAT = 11.5230;
    private const LON = 7.3120;

    private array $osmAddress = [];
    private array $osmElements = [];
    private int $nominatimStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['geocoding.enabled' => true, 'geocoding.nominatim.min_interval_ms' => 0]);
        // Registered once: the first matching fake wins, so each test only changes the answers.
        Http::preventStrayRequests();
        Http::fake([
            'nominatim.openstreetmap.org/*' => fn () => Http::response(
                $this->nominatimStatus === 200 ? ['address' => $this->osmAddress] : ['error' => 'down'], $this->nominatimStatus),
            'overpass-api.de/*' => fn () => Http::response(['elements' => $this->osmElements]),
        ]);
    }

    private function fakeOsm(array $address, array $elements, int $nominatimStatus = 200): void
    {
        $this->osmAddress = $address;
        $this->osmElements = $elements;
        $this->nominatimStatus = $nominatimStatus;
    }

    private function funtuaElements(array $settlementAt = [11.5240, 7.3130]): array
    {
        return [
            ['type' => 'node', 'lat' => $settlementAt[0], 'lon' => $settlementAt[1], 'tags' => ['place' => 'town', 'name' => 'Funtua']],
            ['type' => 'node', 'lat' => 11.5250, 'lon' => 7.3140, 'tags' => ['amenity' => 'school', 'name' => 'Government Day Secondary School']],
            ['type' => 'node', 'lat' => 11.5400, 'lon' => 7.3300, 'tags' => ['amenity' => 'marketplace', 'name' => 'Far Market']],   // over 2 km away
            ['type' => 'way', 'tags' => ['highway' => 'primary', 'name' => 'Zaria Road'],
                'geometry' => [['lat' => 11.5225, 'lon' => 7.3100], ['lat' => 11.5235, 'lon' => 7.3140]]],
        ];
    }

    private function lookup(array $query = []): \Illuminate\Testing\TestResponse
    {
        $user = User::factory()->create(['role' => 'farmer']);

        return $this->actingAs($user)->getJson(route('diagnostics.location-lookup', ['lat' => self::LAT, 'lon' => self::LON] + $query))->assertOk();
    }

    public function test_suggests_community_landmark_and_postal_code_inside_the_same_lga(): void
    {
        $this->fakeOsm(['town' => 'Funtua', 'state' => 'Katsina', 'postcode' => '831101'], $this->funtuaElements());

        $this->lookup(['accuracy' => 8])->assertJson([
            'found' => true, 'state' => 'Katsina', 'lga' => 'Funtua',
            'suggestions' => [
                'ok' => true,
                'community' => 'Funtua',
                'landmark' => 'Zaria Road, near Government Day Secondary School',
                'postal_code' => '831101',
                'attribution' => '© OpenStreetMap contributors',
            ],
        ]);
    }

    public function test_a_settlement_outside_the_points_lga_is_not_suggested(): void
    {
        // Nominatim names a settlement whose only match lies in Lagos.
        $this->fakeOsm(['village' => 'Funtua', 'postcode' => '831101'], $this->funtuaElements([6.4541, 3.3947]));

        $res = $this->lookup()->assertJson(['found' => true, 'lga' => 'Funtua']);
        $this->assertNull($res->json('suggestions.community'));
    }

    public function test_village_comes_before_town_and_an_unplaceable_name_is_left_empty(): void
    {
        // The village has no matching settlement element, so its LGA cannot be checked.
        $this->fakeOsm(['village' => 'Unknown Hamlet', 'town' => 'Funtua'], $this->funtuaElements());

        $this->assertNull($this->lookup()->json('suggestions.community'));
    }

    public function test_no_postal_code_is_invented(): void
    {
        foreach ([[], ['postcode' => '83110'], ['postcode' => 'Funtua'], ['postcode' => '8311011']] as $address) {
            $this->fakeOsm($address + ['town' => 'Funtua'], $this->funtuaElements());
            $this->assertNull($this->lookup()->json('suggestions.postal_code'), json_encode($address));
            \Illuminate\Support\Facades\Cache::flush();
        }
    }

    public function test_no_named_place_within_500_m_leaves_the_landmark_empty(): void
    {
        $elements = array_values(array_filter($this->funtuaElements(), fn ($e) => ($e['tags']['amenity'] ?? '') !== 'school'));
        $this->fakeOsm(['town' => 'Funtua'], $elements);

        $this->assertNull($this->lookup()->json('suggestions.landmark'));
    }

    public function test_a_low_accuracy_reading_fills_nothing_and_asks_nothing(): void
    {
        $this->fakeOsm(['town' => 'Funtua'], $this->funtuaElements());

        $this->lookup(['accuracy' => 1500])->assertExactJson(['found' => false, 'low_accuracy' => true]);
        Http::assertNothingSent();

        // A pin the farmer placed (no accuracy) is looked up as usual.
        $this->lookup()->assertJson(['found' => true, 'suggestions' => ['community' => 'Funtua']]);
    }

    public function test_a_failed_lookup_leaves_the_suggestions_empty_and_state_lga_still_work(): void
    {
        $this->fakeOsm([], [], 503);

        $this->lookup(['accuracy' => 10])->assertJson([
            'found' => true, 'state' => 'Katsina', 'lga' => 'Funtua',
            'suggestions' => ['ok' => false, 'community' => null, 'landmark' => null, 'postal_code' => null],
        ]);
    }

    public function test_only_the_rounded_point_is_sent_with_our_user_agent_and_the_answer_is_cached(): void
    {
        $this->fakeOsm(['town' => 'Funtua'], $this->funtuaElements());

        $this->lookup();
        $this->lookup(['lat' => 11.52304, 'lon' => 7.31201]);   // same rounded point

        Http::assertSentCount(2);   // one Nominatim + one Overpass, then the cache
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'nominatim')
            && str_contains($r->url(), 'lat=11.523&') && str_contains($r->url(), 'lon=7.312&')
            && str_starts_with($r->header('User-Agent')[0] ?? '', 'MSAS-FarmAI/'));
    }

    public function test_the_off_switch_sends_nothing(): void
    {
        config(['geocoding.enabled' => false]);
        $this->fakeOsm(['town' => 'Funtua'], $this->funtuaElements());

        $this->lookup()->assertJson(['found' => true, 'lga' => 'Funtua', 'suggestions' => ['ok' => false, 'community' => null]]);
        Http::assertNothingSent();
    }

    public function test_kept_suggestions_are_recorded_as_gps_and_typed_or_edited_values_as_user(): void
    {
        $this->fakeAiEngine();
        $user = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => UploadedFile::fake()->createWithContent('leaf.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00fake-image-bytes"),
            'loc_latitude' => (string) self::LAT, 'loc_longitude' => (string) self::LON, 'loc_accuracy_meters' => '8',
            'loc_capture_method' => 'gps_device', 'loc_location_source' => 'gps', 'loc_place_names_source' => 'lookup',
            'loc_state' => 'Katsina', 'loc_lga' => 'Funtua',
            'loc_community' => 'Funtua', 'loc_community_source' => 'gps',
            'loc_address' => 'Behind the clinic', 'loc_address_source' => 'user',
            'loc_postal_code' => '831101', 'loc_postal_code_source' => 'gps',
            'loc_ward' => 'Ward 3', 'loc_ward_source' => 'gps',   // ward is never a suggestion
        ])->assertRedirect(route('diagnostics.history'));

        $loc = CollectionLocation::firstOrFail();
        $this->assertSame('gps', $loc->field_sources['community']);
        $this->assertSame('user', $loc->field_sources['address_landmark']);
        $this->assertSame('gps', $loc->field_sources['postal_code']);
        $this->assertSame('user', $loc->field_sources['ward']);
        $this->assertSame('osm_nominatim', $loc->postal_code_source);
        $this->assertStringStartsWith('grid3-2022', $loc->field_sources['lga']);
    }

    public function test_the_server_never_fills_suggested_fields_itself_on_submit(): void
    {
        // An offline capture syncing later: coordinates only. State/LGA come
        // from the boundaries; Community, Landmark and Postal Code stay empty.
        $this->fakeAiEngine();
        $this->fakeOsm(['town' => 'Funtua', 'postcode' => '831101'], $this->funtuaElements());
        $user = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => UploadedFile::fake()->createWithContent('leaf.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00fake-image-bytes"),
            'loc_latitude' => (string) self::LAT, 'loc_longitude' => (string) self::LON, 'loc_capture_method' => 'gps_device',
        ])->assertRedirect(route('diagnostics.history'));

        $loc = CollectionLocation::firstOrFail();
        $this->assertSame('Funtua', $loc->lga);
        $this->assertNull($loc->community);
        $this->assertNull($loc->address_landmark);
        $this->assertNull($loc->postal_code);
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'nominatim') || str_contains($r->url(), 'overpass'));
    }
}
