<?php

namespace Tests\Integration;

use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Wraps each test in a transaction against the sfmtp_test database (see
 * app/config/config.testing.php) and rolls it back afterward, so integration
 * tests can freely INSERT real rows without polluting the test database
 * between runs.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Database::connection();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        parent::tearDown();
    }

    protected function createFarm(string $name = 'Test Farm'): int
    {
        $this->pdo->prepare('INSERT INTO farms (name) VALUES (:name)')->execute(['name' => $name]);
        $id = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("UPDATE farms SET code = CONCAT('FARM', LPAD(:id, 2, '0')) WHERE id = :id2")
            ->execute(['id' => $id, 'id2' => $id]);
        return $id;
    }

    protected function createBlock(int $farmId, string $name = 'Block A'): int
    {
        $this->pdo->prepare('INSERT INTO blocks (farm_id, name) VALUES (:farm_id, :name)')
            ->execute(['farm_id' => $farmId, 'name' => $name]);
        return (int) $this->pdo->lastInsertId();
    }

    protected function createPlot(int $blockId, string $plotCode = 'P1', float $hectares = 1.0): int
    {
        $this->pdo->prepare('INSERT INTO plots (block_id, plot_code, size_hectares) VALUES (:block_id, :code, :hectares)')
            ->execute(['block_id' => $blockId, 'code' => $plotCode, 'hectares' => $hectares]);
        return (int) $this->pdo->lastInsertId();
    }

    protected function createCropType(string $name = 'Cocoa'): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM crop_types WHERE name = :name');
        $stmt->execute(['name' => $name]);
        if ($existing = $stmt->fetchColumn()) {
            return (int) $existing;
        }
        $this->pdo->prepare('INSERT INTO crop_types (name) VALUES (:name)')->execute(['name' => $name]);
        return (int) $this->pdo->lastInsertId();
    }

    protected function findOrCreateAdminUser(): int
    {
        $stmt = $this->pdo->query("SELECT id FROM users LIMIT 1");
        if ($existing = $stmt->fetchColumn()) {
            return (int) $existing;
        }

        $roleStmt = $this->pdo->query("SELECT id FROM roles LIMIT 1");
        $roleId = $roleStmt->fetchColumn();
        if (!$roleId) {
            $this->pdo->exec("INSERT INTO roles (name, slug) VALUES ('Tester', 'tester')");
            $roleId = (int) $this->pdo->lastInsertId();
        }

        $this->pdo->prepare("INSERT INTO users (name, email, password_hash, role_id) VALUES ('Test User', :email, 'x', :role_id)")
            ->execute(['email' => 'test' . uniqid() . '@example.test', 'role_id' => $roleId]);
        return (int) $this->pdo->lastInsertId();
    }
}
