<?php
/**
 * Tenant detection configuration.
 *
 * Tenants (organizations) are addressed by URL path segment for the public
 * traceability pages: /org/{slug}/... . Inside org-admin/ and worker/, the
 * active organization is instead derived from the authenticated user's
 * session (organization_users membership) rather than the URL, since a
 * single shared login can belong to more than one organization.
 */

return [
    'detection_mode' => 'path', // path | subdomain (subdomain reserved for future use)
    'public_prefix' => 'org',
    'reserved_slugs' => ['admin', 'org-admin', 'worker', 'public', 'api', 'assets', 'www'],
];
