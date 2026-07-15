<?php

use PHPUnit\Framework\TestCase;

/**
 * Exercises the tenant_*() helpers (includes/db.php) against a real
 * database to prove multi-tenant isolation actually holds: a row created
 * under one organization must never be readable/writable through another
 * organization's id.
 */
final class TenantScopingTest extends TestCase
{
    private int $orgAId;
    private int $orgBId;

    protected function setUp(): void
    {
        if (!test_db_available()) {
            $this->markTestSkipped('No database configured for this environment.');
        }

        require_once __DIR__ . '/../../includes/db.php';

        $this->orgAId = db_insert('organizations', [
            'name' => 'Test Org A ' . uniqid(),
            'slug' => 'test-org-a-' . uniqid(),
            'subscription_plan' => 'free',
            'subscription_status' => 'active',
        ]);

        $this->orgBId = db_insert('organizations', [
            'name' => 'Test Org B ' . uniqid(),
            'slug' => 'test-org-b-' . uniqid(),
            'subscription_plan' => 'free',
            'subscription_status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        if (!test_db_available()) {
            return;
        }

        db_delete('organizations', 'id IN (:a, :b)', ['a' => $this->orgAId, 'b' => $this->orgBId]);
    }

    public function test_tenant_insert_and_find_round_trip(): void
    {
        $farmId = tenant_insert('farms', $this->orgAId, ['name' => 'Test Farm', 'size' => 10]);

        $found = tenant_find('farms', $this->orgAId, $farmId);
        $this->assertNotNull($found);
        $this->assertSame('Test Farm', $found['name']);
    }

    public function test_tenant_find_returns_null_for_wrong_organization(): void
    {
        $farmId = tenant_insert('farms', $this->orgAId, ['name' => 'Org A Farm', 'size' => 10]);

        $foundUnderWrongOrg = tenant_find('farms', $this->orgBId, $farmId);
        $this->assertNull($foundUnderWrongOrg, 'A farm belonging to org A must not be visible when queried under org B.');
    }

    public function test_tenant_update_does_not_affect_other_organizations_row(): void
    {
        $farmId = tenant_insert('farms', $this->orgAId, ['name' => 'Original Name', 'size' => 10]);

        $rowsAffected = tenant_update('farms', $this->orgBId, $farmId, ['name' => 'Hijacked Name']);
        $this->assertSame(0, $rowsAffected, 'Updating a farm through the wrong organization id must affect zero rows.');

        $stillOriginal = tenant_find('farms', $this->orgAId, $farmId);
        $this->assertSame('Original Name', $stillOriginal['name']);
    }

    public function test_tenant_delete_does_not_affect_other_organizations_row(): void
    {
        $farmId = tenant_insert('farms', $this->orgAId, ['name' => 'Should Survive', 'size' => 10]);

        $rowsDeleted = tenant_delete('farms', $this->orgBId, $farmId);
        $this->assertSame(0, $rowsDeleted);

        $this->assertNotNull(tenant_find('farms', $this->orgAId, $farmId));

        // Clean up for real using the correct org id.
        tenant_delete('farms', $this->orgAId, $farmId);
    }

    public function test_tenant_count_only_counts_own_organization(): void
    {
        tenant_insert('farms', $this->orgAId, ['name' => 'A1', 'size' => 5]);
        tenant_insert('farms', $this->orgAId, ['name' => 'A2', 'size' => 5]);
        tenant_insert('farms', $this->orgBId, ['name' => 'B1', 'size' => 5]);

        $this->assertSame(2, tenant_count('farms', $this->orgAId));
        $this->assertSame(1, tenant_count('farms', $this->orgBId));
    }
}
