<?php

namespace Tests\Unit;

use App\Services\LocationValidationService;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit coverage for coordinate validation (spec Section 5.3 / 12's
 * "Location tests": invalid coordinates, coordinates outside Nigeria,
 * lat/lon reversal). No DB — mirrors the AiResponseNormalizerTest pattern.
 */
class LocationValidationServiceTest extends TestCase
{
    public function test_accepts_a_real_nigerian_coordinate(): void
    {
        $result = LocationValidationService::validate(['latitude' => 11.0804, 'longitude' => 7.3147, 'country' => 'Nigeria']); // Katsina

        $this->assertTrue($result['valid']);
        $this->assertSame('unverified', $result['verification_status']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_rejects_latitude_outside_global_range(): void
    {
        $result = LocationValidationService::validate(['latitude' => 190.0, 'longitude' => 7.0, 'country' => 'Nigeria']);

        $this->assertFalse($result['valid']);
        $this->assertSame('boundary_mismatch_flagged', $result['verification_status']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_detects_classic_lat_lon_reversal(): void
    {
        // A real Lagos coordinate (6.5244 N, 3.3792 E) entered backwards.
        $this->assertTrue(LocationValidationService::looksLatLonReversed(3.3792, 6.5244));
        $this->assertFalse(LocationValidationService::looksLatLonReversed(6.5244, 3.3792));
    }

    public function test_flags_but_does_not_reject_coordinates_outside_nigeria(): void
    {
        // London — globally valid, but implausible for a Nigeria-labelled sample.
        $result = LocationValidationService::validate(['latitude' => 51.5074, 'longitude' => -0.1278, 'country' => 'Nigeria']);

        $this->assertTrue($result['valid'], 'A valid global coordinate must never be hard-rejected, only flagged.');
        $this->assertSame('boundary_mismatch_flagged', $result['verification_status']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_missing_coordinates_are_valid_and_unflagged(): void
    {
        // No GPS at all is a legitimate, common case (spec Section 11) — must
        // never be treated as an error.
        $result = LocationValidationService::validate(['latitude' => null, 'longitude' => null]);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_does_not_apply_nigeria_bounding_box_to_other_countries(): void
    {
        $result = LocationValidationService::validate(['latitude' => 51.5074, 'longitude' => -0.1278, 'country' => 'United Kingdom']);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['warnings']);
    }
}
