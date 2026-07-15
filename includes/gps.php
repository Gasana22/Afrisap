<?php
/**
 * GPS / location utilities.
 */

function gps_valid(?float $lat, ?float $lng): bool
{
    return $lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
}

/** Haversine distance in kilometers between two GPS points. */
function gps_distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

function gps_map_embed_url(?float $lat, ?float $lng): ?string
{
    if (!gps_valid($lat, $lng)) {
        return null;
    }

    return "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lng}#map=15/{$lat}/{$lng}";
}
