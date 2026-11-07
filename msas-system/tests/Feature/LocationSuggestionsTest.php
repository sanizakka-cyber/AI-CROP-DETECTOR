<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

    /** Nominatim answers: the point (zoom 18), its settlement (zoom 14), a settlement search. */
    private array $address = [];
    private ?array $road = null;
    private array $settlement = [];
    private array $search = [];
    private int $nominatimStatus = 200;
    private string $placesDir;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        // Our own copy of OpenStreetMap places, for these tests: one half-degree cell around Funtua.
        $this->placesDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'msas-places-' . uniqid();
        mkdir($this->placesDir);
        config(['geocoding.enabled' => true, 'geocoding.nominatim.min_interval_ms' => 0, 'geocoding.places_path' => $this->placesDir]);
        \App\Services\NearbyPlaces::forget();
        // Registered once (the first matching fake wins); each test sets the answers.
        Http::preventStrayRequests();
        Http::fake([
            'nominatim.openstreetmap.org/*' => function (HttpRequest $r) {
                if ($this->nominatimStatus !== 200) {
                    return Http::response(['error' => 'down'], $this->nominatimStatus);
                }
                if (str_contains($r->url(), '/search')) {
                    return Http::response($this->search);
                }
                return Http::response(str_contains($r->url(), 'zoom=14') ? $this->settlement : ['address' => $this->address] + ($this->road ?? []));
            },
        ]);
        $this->funtua();
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->placesDir . DIRECTORY_SEPARATOR . '*.json') ?: []);
        @rmdir($this->placesDir);
        \App\Services\NearbyPlaces::forget();
        parent::tearDown();
    }

    private function places(array $rows): void
    {
        file_put_contents($this->placesDir . DIRECTORY_SEPARATOR . '23_14.json', json_encode($rows));   // floor(11.523*2)_floor(7.312*2)
        \App\Services\NearbyPlaces::forget();
    }

    /** Funtua as OpenStreetMap describes it: Nominatim's settlement level is a district, the town is in the address. */
    private function funtua(): void
    {
        $this->address = ['city_district' => 'Dandutse', 'town' => 'Funtua', 'state' => 'Katsina State', 'postcode' => '831101'];
        // Nominatim's object for the point is the road it lies on, with its shape.
        $this->road = ['category' => 'highway', 'type' => 'primary', 'name' => 'Zaria Road',
            'geojson' => ['type' => 'LineString', 'coordinates' => [[7.3100, 11.5225], [7.3140, 11.5235]]]];
        $this->settlement = ['addresstype' => 'city_district', 'name' => 'Dandutse', 'lat' => '11.5161', 'lon' => '7.3157', 'address' => $this->address];
        $this->search = [['addresstype' => 'town', 'name' => 'Funtua', 'lat' => '11.5232', 'lon' => '7.3075']];
        $this->places([
            [11.5250, 7.3140, 'school', 'Government Day Secondary School'],   // about 310 m away
            [11.5400, 7.3300, 'market', 'Far Market'],                        // over 2 km away
        ]);
    }

    private function lookup(array $query = [], bool $suggest = true): \Illuminate\Testing\TestResponse
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $params = ['lat' => self::LAT, 'lon' => self::LON] + ($suggest ? ['suggest' => 1] : []) + $query;

        return $this->actingAs($user)->getJson(route('diagnostics.location-lookup', $params))->assertOk();
    }

    public function test_suggests_community_landmark_and_postal_code_inside_the_same_lga(): void
    {
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

    public function test_a_village_at_settlement_level_is_used_directly(): void
    {
        $this->settlement = ['addresstype' => 'village', 'name' => 'Dandume Gari', 'lat' => '11.5245', 'lon' => '7.3130', 'address' => []];

        $this->assertSame('Dandume Gari', $this->lookup()->json('suggestions.community'));
    }

    public function test_a_settlement_outside_the_points_lga_is_not_suggested(): void
    {
        // Nominatim's village lies in Lagos, not in Funtua LGA.
        $this->settlement = ['addresstype' => 'village', 'name' => 'Faraway', 'lat' => '6.4541', 'lon' => '3.3947', 'address' => []];
        $this->assertNull($this->lookup()->json('suggestions.community'));

        // The town in the address is only found in another LGA.
        Cache::flush();
        $this->funtua();
        $this->search = [['addresstype' => 'town', 'name' => 'Funtua', 'lat' => '6.4541', 'lon' => '3.3947']];
        $res = $this->lookup()->assertJson(['found' => true, 'lga' => 'Funtua']);
        $this->assertNull($res->json('suggestions.community'));
    }

    public function test_a_settlement_that_cannot_be_placed_is_left_empty(): void
    {
        $this->search = [];
        $this->assertNull($this->lookup()->json('suggestions.community'));
    }

    public function test_no_postal_code_is_invented(): void
    {
        foreach ([null, '83110', 'Funtua', '8311011'] as $postcode) {
            Cache::flush();
            $this->funtua();
            if ($postcode === null) {
                unset($this->address['postcode']);
            } else {
                $this->address['postcode'] = $postcode;
            }
            $this->assertNull($this->lookup()->json('suggestions.postal_code'), (string) $postcode);
        }
    }

    public function test_a_postal_code_is_suggested_only_when_its_first_digits_match_the_state(): void
    {
        // Abuja (FCT) codes start with 90/91; OpenStreetMap gave 223140 there on 7 Nov 2026.
        $abuja = ['lat' => 9.0579, 'lon' => 7.4951];
        $this->address = ['postcode' => '223140'];
        $this->assertNull($this->lookup($abuja)->json('suggestions.postal_code'));

        Cache::flush();
        $this->address = ['postcode' => '900211'];
        $this->assertSame('900211', $this->lookup($abuja)->json('suggestions.postal_code'));

        // A Katsina code at a Katsina point is kept; a Kano code there is not.
        Cache::flush();
        $this->funtua();
        $this->assertSame('831101', $this->lookup()->json('suggestions.postal_code'));
        Cache::flush();
        $this->address['postcode'] = '700241';
        $this->assertNull($this->lookup()->json('suggestions.postal_code'));

        // A State with no known prefixes gets no suggestion at all.
        Cache::flush();
        $this->funtua();
        config(['geocoding.postcode_prefixes' => ['Lagos' => ['10']]]);
        $this->assertNull($this->lookup()->json('suggestions.postal_code'));
    }

    public function test_no_named_place_within_500_m_leaves_the_landmark_empty(): void
    {
        $this->places([[11.5400, 7.3300, 'market', 'Far Market']]);

        $this->assertNull($this->lookup()->json('suggestions.landmark'));
    }

    public function test_a_road_further_than_200_m_is_left_out_of_the_landmark(): void
    {
        $this->road['geojson'] = ['type' => 'LineString', 'coordinates' => [[7.3200, 11.5300], [7.3250, 11.5310]]];   // about 1 km away

        $this->assertSame('Near Government Day Secondary School', $this->lookup()->json('suggestions.landmark'));
    }

    public function test_the_landmark_comes_from_our_own_copy_and_nothing_is_sent_for_it(): void
    {
        // Even with Nominatim down, the landmark is found on our server (without a road name).
        $this->nominatimStatus = 503;

        $this->lookup()->assertJson(['suggestions' => [
            'ok' => true, 'community' => null, 'postal_code' => null, 'landmark' => 'Near Government Day Secondary School',
        ]]);
        Http::assertNotSent(fn (HttpRequest $r) => !str_contains($r->url(), 'nominatim.openstreetmap.org'));
    }

    public function test_a_facility_name_is_never_suggested_as_a_community(): void
    {
        // As OpenStreetMap had Katsina city on 7 Nov 2026.
        $this->address = ['city_district' => 'Kudu 2', 'city' => 'Muhammadu Dikko Stadium'];
        $this->settlement = ['addresstype' => 'city_district', 'name' => 'Gabas 1', 'lat' => '11.5245', 'lon' => '7.3130', 'address' => $this->address];
        $this->search = [['addresstype' => 'city', 'name' => 'Muhammadu Dikko Stadium', 'lat' => '11.5240', 'lon' => '7.3130']];

        $this->assertNull($this->lookup()->json('suggestions.community'));
    }

    public function test_a_low_accuracy_reading_fills_nothing_and_asks_nothing(): void
    {
        $this->lookup(['accuracy' => 1500])->assertExactJson(['found' => false, 'low_accuracy' => true]);
        Http::assertNothingSent();

        // A pin the farmer placed (no accuracy) is looked up as usual.
        $this->lookup()->assertJson(['found' => true, 'suggestions' => ['community' => 'Funtua']]);
    }

    public function test_a_failed_lookup_leaves_the_suggestions_empty_and_state_lga_still_work(): void
    {
        $this->nominatimStatus = 503;
        $this->places([]);   // and no place near the point in our own copy

        $this->lookup(['accuracy' => 10])->assertJson([
            'found' => true, 'state' => 'Katsina', 'lga' => 'Funtua',
            'suggestions' => ['ok' => false, 'community' => null, 'landmark' => null, 'postal_code' => null],
        ]);
    }

    public function test_without_suggest_the_answer_is_state_and_lga_only_and_nothing_is_sent(): void
    {
        // What app builds that do not ask for suggestions get: the same fast answer as before.
        $res = $this->lookup([], false)->assertJson(['found' => true, 'state' => 'Katsina', 'lga' => 'Funtua']);
        $this->assertArrayNotHasKey('suggestions', $res->json());
        Http::assertNothingSent();
    }

    public function test_only_the_rounded_point_is_sent_with_our_user_agent_and_the_answers_are_cached(): void
    {
        $this->lookup();
        $sent = Http::recorded()->count();
        $this->lookup(['lat' => 11.52304, 'lon' => 7.31201]);   // same rounded point

        $this->assertSame($sent, Http::recorded()->count(), 'the second lookup is answered from the cache');
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'nominatim')
            && str_contains($r->url(), 'lat=11.523&') && str_contains($r->url(), 'lon=7.312&')
            && str_starts_with($r->header('User-Agent')[0] ?? '', 'MSAS-FarmAI/'));
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '11.5230') || str_contains($r->url(), '11.52304'));
    }

    public function test_the_off_switch_sends_nothing(): void
    {
        config(['geocoding.enabled' => false]);

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

    public function test_the_profile_lookup_works_for_every_signed_in_role(): void
    {
        $this->settlement = ['addresstype' => 'village', 'name' => 'Dandume Gari', 'lat' => '11.5245', 'lon' => '7.3130', 'address' => []];
        $user = User::factory()->create(['role' => 'investor']);

        $this->actingAs($user)->getJson(route('location.lookup', ['lat' => self::LAT, 'lon' => self::LON, 'suggest' => 1]))
            ->assertOk()->assertJson(['found' => true, 'lga' => 'Funtua', 'suggestions' => ['community' => 'Dandume Gari']]);
        $this->actingAs($user)->getJson(route('location.lookup', ['lat' => self::LAT, 'lon' => self::LON, 'accuracy' => 2000]))
            ->assertExactJson(['found' => false, 'low_accuracy' => true]);
        auth()->logout();
        $this->getJson(route('location.lookup', ['lat' => self::LAT, 'lon' => self::LON]))->assertUnauthorized();
    }

    public function test_profile_records_where_state_lga_and_ward_came_from(): void
    {
        $user = User::factory()->create(['role' => 'farmer', 'first_name' => 'Amina', 'last_name' => 'Bello']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => 'Amina', 'last_name' => 'Bello', 'email' => $user->email,
            'country' => 'Nigeria', 'state' => 'Katsina', 'lga' => 'Funtua', 'ward' => 'Funtua',
            'state_source' => 'gps', 'lga_source' => 'gps', 'ward_source' => 'gps',
        ])->assertSessionHasNoErrors();
        $this->assertSame(['state' => 'gps', 'lga' => 'gps', 'ward' => 'gps'], $user->fresh()->location_field_sources);

        // The user edits Ward/Village; an unknown source value counts as the user's.
        $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => 'Amina', 'last_name' => 'Bello', 'email' => $user->email,
            'country' => 'Nigeria', 'state' => 'Katsina', 'lga' => 'Funtua', 'ward' => 'Unguwar Sarki',
            'state_source' => 'gps', 'lga_source' => 'boundary', 'ward_source' => 'user',
        ])->assertSessionHasNoErrors();
        $this->assertSame(['state' => 'gps', 'lga' => 'user', 'ward' => 'user'], $user->fresh()->location_field_sources);
    }

    public function test_the_app_profile_records_where_state_lga_and_village_came_from(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $auth = ['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken];

        $this->withHeaders($auth)->patchJson('/api/auth/profile', [
            'state' => 'Katsina', 'lga' => 'Funtua', 'village' => 'Dandume Gari',
            'state_source' => 'gps', 'lga_source' => 'gps', 'village_source' => 'gps',
        ])->assertOk();
        $this->assertSame(['state' => 'gps', 'lga' => 'gps', 'village' => 'gps'], $user->fresh()->location_field_sources);

        // Clearing the village drops its source; a typed LGA is the user's.
        $this->withHeaders($auth)->patchJson('/api/auth/profile', [
            'lga' => 'Bakori', 'village' => null, 'lga_source' => 'user',
        ])->assertOk();
        $this->assertSame(['state' => 'gps', 'lga' => 'user'], $user->fresh()->location_field_sources);
    }

    public function test_the_server_never_fills_suggested_fields_itself_on_submit(): void
    {
        // An offline capture syncing later: coordinates only. State/LGA come
        // from the boundaries; Community, Landmark and Postal Code stay empty.
        $this->fakeAiEngine();
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
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'nominatim'));
    }
}
