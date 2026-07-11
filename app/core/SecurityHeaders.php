<?php

namespace App\Core;

class SecurityHeaders
{
    public static function apply(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(self), camera=(self), microphone=()');

        // Inline <script>/<style> are used throughout the server-rendered views (chart config,
        // map markers, form widgets) without a build step, so this is a pragmatic baseline —
        // it locks external resource loading down to the two CDNs and OSM tiles the app actually
        // uses, not a strict nonce-based policy. Escaping (htmlspecialchars) remains the primary
        // XSS defense; this is defense in depth on top of it.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com",
            "img-src 'self' data: https://*.tile.openstreetmap.org",
            "font-src 'self' https://cdn.jsdelivr.net data:",
            "connect-src 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        header("Content-Security-Policy: {$csp}");
    }
}
