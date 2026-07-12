<?php
/**
 * Returns a shared PDO connection. Call db() anywhere after bootstrap.php
 * has been required.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[DB CONNECTION FAILED] ' . $e->getMessage());
            http_response_code(500);
            exit(APP_DEBUG
                ? 'Database connection failed: ' . $e->getMessage()
                : 'Database connection failed.');
        }
    }

    return $pdo;
}
