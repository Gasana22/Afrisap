<?php

use PHPUnit\Framework\TestCase;

final class GpsTest extends TestCase
{
    public function test_gps_valid_accepts_real_coordinates(): void
    {
        $this->assertTrue(gps_valid(-1.9441, 30.0619));
        $this->assertFalse(gps_valid(null, 30.0619));
        $this->assertFalse(gps_valid(-95, 30.0619));
        $this->assertFalse(gps_valid(-1.9441, 200));
    }

    public function test_gps_distance_km_is_zero_for_same_point(): void
    {
        $this->assertEqualsWithDelta(0.0, gps_distance_km(-1.9441, 30.0619, -1.9441, 30.0619), 0.0001);
    }

    public function test_gps_distance_km_between_known_points(): void
    {
        // Kigali to Musanze, Rwanda - roughly 75-90km apart.
        $distance = gps_distance_km(-1.9441, 30.0619, -1.4998, 29.6339);
        $this->assertGreaterThan(60.0, $distance);
        $this->assertLessThan(100.0, $distance);
    }
}
