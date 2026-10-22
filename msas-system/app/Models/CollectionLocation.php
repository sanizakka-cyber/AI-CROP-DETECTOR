<?php

namespace App\Models;

use App\Services\LgaLookup;
use App\Services\LocationValidationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Where a scanned sample was actually collected — deliberately separate from
 * the Diagnosis row's own created_at (when the scan was PROCESSED). A leaf
 * collected in one LGA and photographed in a lab elsewhere keeps its real
 * collection location here; `differs_from_scan_location` records that the
 * two places are not the same (spec Section 5.1, item 9).
 *
 * One row per diagnosis, always optional — a diagnosis with no row here
 * simply has no location recorded. Never backfilled from the user's
 * registered address, device IP, or server region.
 */
class CollectionLocation extends Model
{
    protected $fillable = [
        'diagnosis_id',
        'country', 'state', 'lga', 'ward', 'community',
        'postal_code', 'postal_code_source', 'address_landmark',
        'latitude', 'longitude', 'accuracy_meters', 'elevation_meters',
        'capture_method', 'coordinate_source', 'user_confirmed',
        'verification_status', 'differs_from_scan_location',
        'collected_at', 'timezone',
        'location_dataset_version', 'collector_identifier', 'notes',
        'location_source', 'gps_captured_at', 'place_names_source',
        'scan_latitude', 'scan_longitude', 'scan_accuracy_meters',
    ];

    /** How the farmer entered the location: GPS, by hand, or GPS then changed. */
    public const LOCATION_SOURCES = ['gps', 'manual', 'gps_edited'];

    protected $casts = [
        'latitude'                    => 'float',
        'longitude'                   => 'float',
        'accuracy_meters'             => 'float',
        'elevation_meters'            => 'float',
        'user_confirmed'              => 'boolean',
        'differs_from_scan_location'  => 'boolean',
        'collected_at'                => 'datetime',
        'gps_captured_at'             => 'datetime',
        'scan_latitude'               => 'float',
        'scan_longitude'              => 'float',
        'scan_accuracy_meters'        => 'float',
    ];

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * Shared by the web (DiagnosticController) and mobile
     * (DiagnoseApiController) scan flows — one implementation of "turn a
     * request's loc_* fields into a stored location" so a mobile scan and a
     * web scan of the same information produce the same record, instead of
     * one client silently discarding fields the other captures (the exact
     * drift DiagnosisResultMapper's docblock already found once in this
     * codebase for diagnosis fields).
     *
     * Creates nothing — not even an empty row — when the client submitted
     * no location fields at all. Never substitutes the user's registered
     * address, device IP, or server region (spec 5.1).
     */
    public static function createFromRequest(Request $request, Diagnosis $diagnosis): ?self
    {
        $hasAnyLocationInput = $request->filled('loc_state') || $request->filled('loc_lga')
            || $request->filled('loc_latitude') || $request->filled('loc_longitude')
            || $request->filled('loc_address') || $request->filled('loc_community')
            || $request->filled('loc_scan_latitude');

        if (!$hasAnyLocationInput) {
            return null;
        }

        $lat = $request->filled('loc_latitude') ? (float) $request->input('loc_latitude') : null;
        $lon = $request->filled('loc_longitude') ? (float) $request->input('loc_longitude') : null;
        $accuracy = $request->input('loc_accuracy_meters');
        $country = $request->input('loc_country') ?: 'Nigeria';
        $captureMethod = $request->input('loc_capture_method');
        $differs = (bool) $request->boolean('loc_differs_from_scan');

        $scan = $request->filled('loc_scan_latitude') && $request->filled('loc_scan_longitude')
            ? [(float) $request->input('loc_scan_latitude'), (float) $request->input('loc_scan_longitude'), $request->input('loc_scan_accuracy_meters')]
            : null;

        // Clients from before loc_location_source send the device fix as the
        // collection point even when the farmer ticked "scanning somewhere
        // else". That fix is where the phone is, so it is the scan place.
        if ($differs && $captureMethod === 'gps_device' && $scan === null && !$request->filled('loc_location_source') && $lat !== null) {
            $scan = [$lat, $lon, $accuracy];
            $lat = $lon = $accuracy = null;
            $captureMethod = null;
        }

        $validation = LocationValidationService::validate([
            'latitude' => $lat, 'longitude' => $lon, 'country' => $country,
        ]);

        // A truly invalid pair (already caught by the controller's
        // 'between' validation rule for impossible values) should never
        // reach storage — belt and braces for any caller that skips it.
        if (!$validation['valid']) {
            $lat = null;
            $lon = null;
        }
        if ($scan !== null && !LocationValidationService::validate(['latitude' => $scan[0], 'longitude' => $scan[1], 'country' => $country])['valid']) {
            $scan = null;
        }

        [$state, $lga, $placeNamesSource] = self::placeNames($request, $lat, $lon);

        $locationSource = $request->input('loc_location_source')
            ?: ($captureMethod === 'gps_device' ? 'gps' : 'manual');

        return self::create([
            'diagnosis_id'                => $diagnosis->id,
            'country'                     => $country,
            'state'                       => $state,
            'lga'                         => $lga,
            'ward'                        => $request->input('loc_ward'),
            'community'                   => $request->input('loc_community'),
            'postal_code'                 => $request->input('loc_postal_code'),
            'postal_code_source'          => $request->filled('loc_postal_code') ? 'user_entered' : null,
            'address_landmark'            => $request->input('loc_address'),
            'latitude'                    => $lat,
            'longitude'                   => $lon,
            'accuracy_meters'             => $lat !== null ? $accuracy : null,
            // A blank hidden field submits as "" (not absent), so `??`
            // alone would never reach this fallback chain — `filled()`
            // check is required to actually infer a method when the client
            // didn't explicitly set one.
            'capture_method'              => $captureMethod
                ?: ($lat !== null ? 'manual_coordinates' : ($state ? 'administrative_selection' : 'address_entry')),
            'coordinate_source'           => $lat === null ? null : ($captureMethod === 'gps_device' ? 'device' : 'manual'),
            'user_confirmed'              => (bool) $request->boolean('loc_user_confirmed'),
            'verification_status'         => $validation['verification_status'],
            'differs_from_scan_location'  => $differs,
            'collected_at'                => $request->input('loc_collected_at') ?: now(),
            'timezone'                    => config('app.timezone'),
            'location_dataset_version'    => 'nigeria-states-lgas-v1',
            'location_source'             => $locationSource,
            // Clients send ISO 8601 in UTC; stored in the app timezone like every other timestamp.
            'gps_captured_at'             => $lat !== null && $request->filled('loc_gps_captured_at')
                ? \Illuminate\Support\Carbon::parse($request->input('loc_gps_captured_at'))->setTimezone(config('app.timezone'))
                : null,
            'place_names_source'          => $placeNamesSource,
            'scan_latitude'               => $scan[0] ?? null,
            'scan_longitude'              => $scan[1] ?? null,
            'scan_accuracy_meters'        => $scan[2] ?? null,
        ]);
    }

    /**
     * State and LGA to store, and where they came from: 'user', or the
     * boundary dataset version when LgaLookup supplied them. Blank names
     * with a GPS point are filled from the boundaries (this is how a GPS
     * capture made offline gets its names when it syncs). A client may say
     * its names came from the lookup endpoint (loc_place_names_source=
     * lookup); that is recorded only when the names still match the lookup
     * here, otherwise the farmer changed them and they are the user's.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private static function placeNames(Request $request, ?float $lat, ?float $lon): array
    {
        $state = $request->input('loc_state') ?: null;
        $lga = $request->input('loc_lga') ?: null;
        $found = $lat !== null ? LgaLookup::locate($lat, $lon) : null;

        if ($found && $lga === null && ($state === null || $state === $found['state'])) {
            return [$found['state'], $found['lga'], $found['source']];
        }
        if ($found && $state === $found['state'] && $lga === $found['lga'] && $request->input('loc_place_names_source') === 'lookup') {
            return [$state, $lga, $found['source']];
        }

        return [$state, $lga, $state || $lga ? 'user' : null];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** Human-readable provenance label for the UI (spec 5.5). */
    public function getProvenanceLabelAttribute(): string
    {
        return match (true) {
            $this->location_source === 'gps_edited'                        => 'GPS captured, then changed by the user',
            $this->capture_method === 'gps_device' && $this->user_confirmed => 'GPS captured and user confirmed',
            $this->capture_method === 'gps_device'                         => 'GPS captured but not confirmed',
            $this->capture_method === 'administrative_selection'           => 'User-selected administrative location',
            $this->capture_method === 'manual_coordinates'                 => 'Manually entered coordinates',
            $this->capture_method === 'address_entry'                      => 'Address entered (not geocoded)',
            default                                                        => 'Location unavailable',
        };
    }

    /** Best available locality string for display, skipping missing levels rather than printing blanks. */
    public function getDisplayLocalityAttribute(): ?string
    {
        $parts = array_filter([$this->community, $this->ward, $this->lga, $this->state]);

        return $parts ? implode(', ', $parts) : null;
    }
}
