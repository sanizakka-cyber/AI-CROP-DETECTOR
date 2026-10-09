<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'latitude'                    => 'float',
        'longitude'                   => 'float',
        'accuracy_meters'             => 'float',
        'elevation_meters'            => 'float',
        'user_confirmed'              => 'boolean',
        'differs_from_scan_location'  => 'boolean',
        'collected_at'                => 'datetime',
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
            || $request->filled('loc_address') || $request->filled('loc_community');

        if (!$hasAnyLocationInput) {
            return null;
        }

        $lat = $request->filled('loc_latitude') ? (float) $request->input('loc_latitude') : null;
        $lon = $request->filled('loc_longitude') ? (float) $request->input('loc_longitude') : null;
        $country = $request->input('loc_country') ?: 'Nigeria';

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

        return self::create([
            'diagnosis_id'                => $diagnosis->id,
            'country'                     => $country,
            'state'                       => $request->input('loc_state'),
            'lga'                         => $request->input('loc_lga'),
            'ward'                        => $request->input('loc_ward'),
            'community'                   => $request->input('loc_community'),
            'postal_code'                 => $request->input('loc_postal_code'),
            'postal_code_source'          => $request->filled('loc_postal_code') ? 'user_entered' : null,
            'address_landmark'            => $request->input('loc_address'),
            'latitude'                    => $lat,
            'longitude'                   => $lon,
            'accuracy_meters'             => $request->input('loc_accuracy_meters'),
            // A blank hidden field submits as "" (not absent), so `??`
            // alone would never reach this fallback chain — `filled()`
            // check is required to actually infer a method when the client
            // didn't explicitly set one.
            'capture_method'              => $request->filled('loc_capture_method')
                ? $request->input('loc_capture_method')
                : ($lat !== null ? 'manual_coordinates' : ($request->filled('loc_state') ? 'administrative_selection' : 'address_entry')),
            'coordinate_source'           => $request->input('loc_capture_method') === 'gps_device' ? 'device' : ($lat !== null ? 'manual' : null),
            'user_confirmed'              => (bool) $request->boolean('loc_user_confirmed'),
            'verification_status'         => $validation['verification_status'],
            'differs_from_scan_location'  => (bool) $request->boolean('loc_differs_from_scan'),
            'collected_at'                => $request->input('loc_collected_at') ?: now(),
            'timezone'                    => config('app.timezone'),
            'location_dataset_version'    => 'nigeria-states-lgas-v1',
        ]);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** Human-readable provenance label for the UI (spec 5.5). */
    public function getProvenanceLabelAttribute(): string
    {
        return match (true) {
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
