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
    private array $settlement = [];
    private array $search = [];
    private int $nominatimStatus = 200;
    /** Overpass answer (null: the server does not answer). */
    private ?array $overpass = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['geocoding.enabled' => true, 'geocoding.nominatim.min_interval_ms' => 0]);
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
                return Http::response(str_contains($r->url(), 'zoom=14') ? $this->settlement : ['address' => $this->address]);
            },
            'overpass-api.de/*' => fn () => $this->overpass === null ? Http::response('busy', 504) : Http::response(['elements' => $this->overpass]),
            'overpass.private.coffee/*' => fn () => $this->overpass === null ? Http::response('busy', 504) : Http::response(['elements' => $this->overpass]),
        ]);
        $this->funtua();
    }

    /** Funtua as OpenStreetMap describes it: Nominatim's settlement level is a district, the town is in the address. */
    private function funtua(): void
    {
        $this->address = ['city_district' => 'Dandutse', 'town' => 'Funtua', 'state' => 'Katsina State', 'postcode' => '831101'];
        $this->settlement = ['addresstype' => 'city_district', 'name' => 'Dandutse', 'lat' => '11.5161', 'lon' => '7.3157', 'address' => $this->address];
        $this->search = [['addresstype' => 'town', 'name' => 'Funtua', 'lat' => '11.5232', 'lon' => '7.3075']];
        $this->overpass = [
            ['type' => 'node', 'lat' => 11.5250, 'lon' => 7.3140, 'tags' => ['amenity' => 'school', 'name' => 'Government Day Secondary School']],
            ['type' => 'node', 'lat' => 11.5400, 'lon' => 7.3300, 'tags' => ['amenity' => 'marketplace', 'name' => 'Far Market']],   // over 2 km away
            ['type' => 'way', 'tags' => ['highway' => 'primary', 'name' => 'Zaria Road'],
                'geometry' => [['lat' => 11.5225, 'lon' => 7.3100], ['lat' => 11.5235, 'lon' => 7.3140]]],
        ];
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

    public function test_no_named_place_within_500_m_leaves_the_landmark_empty(): void
    {
        $this->overpass = array_values(array_filter($this->overpass, fn ($e) => ($e['tags']['amenity'] ?? '') !== 'school'));

        $this->assertNull($this->lookup()->json('suggestions.landmark'));
    }

    public function test_when_overpass_is_busy_only_the_landmark_stays_empty(): void
    {
        $this->overpass = null;

        $this->lookup()->assertJson(['suggestions' => ['ok' => true, 'community' => 'Funtua', 'landmark' => null, 'postal_code' => '831101']]);
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
        $this->overpass = null;

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
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'nominatim') || str_contains($r->url(), 'overpass'));
    }
}
