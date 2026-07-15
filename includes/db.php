<?php
/**
 * Database access helpers.
 *
 * Every helper that touches tenant-owned data requires an explicit
 * organization_id so that multi-tenant isolation is enforced at the query
 * layer rather than left to page authors to remember.
 */

require_once __DIR__ . '/../config/database.php';

function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();

    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = []): mixed
{
    $row = db_query($sql, $params)->fetch(PDO::FETCH_NUM);

    return $row === false ? null : $row[0];
}

function db_insert(string $table, array $data): int
{
    $columns = array_keys($data);
    $placeholders = array_map(fn ($c) => ":$c", $columns);

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columns),
        implode(', ', $placeholders)
    );

    db_query($sql, $data);

    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, string $whereSql, array $whereParams = []): int
{
    $set = implode(', ', array_map(fn ($c) => "$c = :set_$c", array_keys($data)));
    $params = [];
    foreach ($data as $key => $value) {
        $params["set_$key"] = $value;
    }
    foreach ($whereParams as $key => $value) {
        $params[$key] = $value;
    }

    $sql = "UPDATE $table SET $set WHERE $whereSql";

    return db_query($sql, $params)->rowCount();
}

function db_delete(string $table, string $whereSql, array $whereParams = []): int
{
    return db_query("DELETE FROM $table WHERE $whereSql", $whereParams)->rowCount();
}

/**
 * Tenant-scoped variants - always filter by organization_id so a page
 * cannot accidentally leak another tenant's rows.
 */

function tenant_all(string $table, int $organizationId, string $extraSql = '', array $params = []): array
{
    $sql = "SELECT * FROM $table WHERE organization_id = :organization_id $extraSql";
    $params['organization_id'] = $organizationId;

    return db_all($sql, $params);
}

function tenant_find(string $table, int $organizationId, int $id): ?array
{
    return db_one(
        "SELECT * FROM $table WHERE organization_id = :organization_id AND id = :id",
        ['organization_id' => $organizationId, 'id' => $id]
    );
}

function tenant_insert(string $table, int $organizationId, array $data): int
{
    $data['organization_id'] = $organizationId;

    return db_insert($table, $data);
}

function tenant_update(string $table, int $organizationId, int $id, array $data): int
{
    return db_update($table, $data, 'organization_id = :organization_id AND id = :id', [
        'organization_id' => $organizationId,
        'id' => $id,
    ]);
}

function tenant_delete(string $table, int $organizationId, int $id): int
{
    return db_delete($table, 'organization_id = :organization_id AND id = :id', [
        'organization_id' => $organizationId,
        'id' => $id,
    ]);
}

function tenant_count(string $table, int $organizationId, string $extraSql = '', array $params = []): int
{
    $params['organization_id'] = $organizationId;

    return (int) db_value("SELECT COUNT(*) FROM $table WHERE organization_id = :organization_id $extraSql", $params);
}
