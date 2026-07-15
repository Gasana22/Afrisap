<?php
/**
 * Shared PHPUnit bootstrap. Loads the app's own bootstrap (config, db
 * helpers, functions) so tests exercise the real helpers rather than
 * reimplementing them. Integration tests need a reachable database
 * matching .env; unit tests that only touch pure functions don't.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/sanitization.php';
require_once __DIR__ . '/../includes/gps.php';

function test_db_available(): bool
{
    try {
        require_once __DIR__ . '/../config/database.php';
        db()->query('SELECT 1');

        return true;
    } catch (Throwable) {
        return false;
    }
}
