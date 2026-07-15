<?php
/**
 * Tenant isolation middleware.
 *
 * Two distinct concerns:
 *   - resolve_tenant_by_slug(): used by the public org/{slug}/ pages where
 *     the tenant comes from the URL and there is no logged-in user.
 *   - active_organization_id(): used by org-admin/ and worker/ pages where
 *     the tenant comes from the authenticated session.
 */

require_once __DIR__ . '/db.php';

function resolve_tenant_by_slug(string $slug): ?array
{
    return db_one(
        "SELECT * FROM organizations WHERE slug = :slug AND subscription_status != 'suspended'",
        ['slug' => $slug]
    );
}

function active_organization_id(): ?int
{
    $id = $_SESSION['organization_id'] ?? $_SESSION['worker_organization_id'] ?? null;

    return $id ? (int) $id : null;
}

function require_tenant_slug(string $slug): array
{
    $org = resolve_tenant_by_slug($slug);

    if (!$org) {
        http_response_code(404);
        echo 'Organization not found.';
        exit;
    }

    return $org;
}

function plan_limits(string $plan): array
{
    $limits = SUBSCRIPTION_PLAN_LIMITS;

    return $limits[$plan] ?? $limits['free'];
}

function organization_farm_count(int $organizationId): int
{
    return tenant_count('farms', $organizationId);
}

function organization_user_count(int $organizationId): int
{
    return (int) db_value(
        'SELECT COUNT(*) FROM organization_users WHERE organization_id = :id AND status = "active"',
        ['id' => $organizationId]
    );
}

function organization_within_plan_limits(array $organization): array
{
    $limits = plan_limits($organization['subscription_plan']);
    $farms = organization_farm_count($organization['id']);
    $users = organization_user_count($organization['id']);

    return [
        'farms' => ['used' => $farms, 'limit' => $limits['max_farms'], 'ok' => $farms < $limits['max_farms']],
        'users' => ['used' => $users, 'limit' => $limits['max_users'], 'ok' => $users < $limits['max_users']],
    ];
}
