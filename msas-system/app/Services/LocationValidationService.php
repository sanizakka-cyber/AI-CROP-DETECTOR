<?php

namespace App\Services;

/**
 * Coordinate and administrative-location validation for sample collection
 * data (spec Section 5.3). Pure, stateless, unit-testable — no model/DB
 * access — so both the web and mobile scan flows validate identically.
 *
 * Never silently "fixes" or replaces a user-selected value. Every method
 * here either confirms a value is plausible or flags it; the caller decides
 * whether to warn, require confirmation, or store the flag — it never
 * rejects a scan outright for a location problem (spec Section 11).
 */
class LocationValidationService
{
    /** Generous Nigeria bounding box — a loose plausibility check, not an exact boundary. */
    private const NIGERIA_LAT_MIN = 4.0;
    private const NIGERIA_LAT_MAX = 14.0;
    private const NIGERIA_LON_MIN = 2.5;
    private const NIGERIA_LON_MAX = 15.0;

    /** True only for coordinates on Earth at all — the universal sanity check regardless of country. */
    public static function isValidGlobalCoordinate(?float $lat, ?float $lon): bool
    {
        if ($lat === null || $lon === null) {
            return false;
        }

        return $lat >= -90.0 && $lat <= 90.0 && $lon >= -180.0 && $lon <= 180.0;
    }

    /**
     * Detects the classic lat/lon-swapped-by-accident bug: the given pair is
     * invalid as (lat, lon) but valid when swapped. Returns true only in
     * that specific case — never claims a swap when both orderings are
     * equally plausible (e.g. both in range) or neither is.
     */
    public static function looksLatLonReversed(float $lat, float $lon): bool
    {
        $asGiven = self::isValidGlobalCoordinate($lat, $lon);
        $swapped = self::isValidGlobalCoordinate($lon, $lat);

        return !$asGiven && $swapped;
    }

    /** Loose "is this plausibly inside Nigeria" check — a bounding box, not a real polygon boundary. */
    public static function isPlausiblyInNigeria(float $lat, float $lon): bool
    {
        return $lat >= self::NIGERIA_LAT_MIN && $lat <= self::NIGERIA_LAT_MAX
            && $lon >= self::NIGERIA_LON_MIN && $lon <= self::NIGERIA_LON_MAX;
    }

    /**
     * Validates a submitted location payload and returns a structured
     * result: whether it's safe to store as-is, and any warnings to show
     * the user or flag on the record. Never throws — a bad location is a
     * warning, not a blocker.
     *
     * @param array{latitude?:float|null, longitude?:float|null, country?:string|null, state?:string|null, lga?:string|null} $data
     * @return array{valid: bool, warnings: string[], verification_status: string}
     */
    public static function validate(array $data): array
    {
        $warnings = [];
        $lat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $lon = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $country = $data['country'] ?? null;

        $hasCoords = $lat !== null && $lon !== null;

        if ($hasCoords && !self::isValidGlobalCoordinate($lat, $lon)) {
            if (self::looksLatLonReversed($lat, $lon)) {
                $warnings[] = 'Latitude and longitude appear to be reversed.';
            } else {
                $warnings[] = 'Coordinates are outside the valid geographic range (latitude -90..90, longitude -180..180).';
            }

            return ['valid' => false, 'warnings' => $warnings, 'verification_status' => 'boundary_mismatch_flagged'];
        }

        if ($hasCoords && (!$country || strcasecmp($country, 'Nigeria') === 0) && !self::isPlausiblyInNigeria($lat, $lon)) {
            $warnings[] = 'These coordinates fall outside Nigeria\'s typical geographic range. Please confirm the location is correct.';

            return ['valid' => true, 'warnings' => $warnings, 'verification_status' => 'boundary_mismatch_flagged'];
        }

        return ['valid' => true, 'warnings' => $warnings, 'verification_status' => $hasCoords ? 'unverified' : 'unverified'];
    }
}
