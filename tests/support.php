<?php
declare(strict_types=1);

/**
 * Minimal test support: DB fixtures, an HTTP client with cookie-jar support,
 * and a tiny assertion/reporting harness. No Composer/PHPUnit dependency,
 * so this runs anywhere PHP + MySQL do (matches the app itself).
 */

const TEST_DB_HOST = '127.0.0.1';
const TEST_DB_NAME = 'safarisap_test';
const TEST_DB_USER = 'safarisap_test_app';
const TEST_DB_PASS = 'SmokeTestPass123!';
const TEST_PORT = 8098;
const TEST_BASE_URL = 'http://127.0.0.1:8098';

function smoke_shell(string $cmd): string
{
    $output = shell_exec($cmd . ' 2>&1');
    return $output === null ? '' : $output;
}

function smoke_setup_database(): void
{
    echo "Setting up test database...\n";

    smoke_shell('mysql -u root -e ' . escapeshellarg(
        'DROP DATABASE IF EXISTS ' . TEST_DB_NAME . '; CREATE DATABASE ' . TEST_DB_NAME . ' CHARACTER SET utf8mb4;'
    ));

    $schemaOut = smoke_shell('mysql -u root ' . TEST_DB_NAME . ' < ' . escapeshellarg(__DIR__ . '/../database/schema.sql'));
    if (str_contains($schemaOut, 'ERROR')) {
        fwrite(STDERR, "schema.sql failed to apply:\n$schemaOut\n");
        exit(1);
    }

    // A dedicated TCP-auth app user -- root's default auth_socket plugin
    // (common on Debian/Ubuntu MySQL/MariaDB packages) doesn't work over
    // TCP, which is what the app's own db.php connects with.
    smoke_shell('mysql -u root -e ' . escapeshellarg(sprintf(
        "CREATE USER IF NOT EXISTS '%s'@'%s' IDENTIFIED BY '%s'; GRANT ALL PRIVILEGES ON %s.* TO '%s'@'%s'; FLUSH PRIVILEGES;",
        TEST_DB_USER, TEST_DB_HOST, TEST_DB_PASS, TEST_DB_NAME, TEST_DB_USER, TEST_DB_HOST
    )));
}

function smoke_teardown_database(): void
{
    smoke_shell('mysql -u root -e ' . escapeshellarg('DROP DATABASE IF EXISTS ' . TEST_DB_NAME . ';'));
}

function smoke_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . TEST_DB_HOST . ';dbname=' . TEST_DB_NAME . ';charset=utf8mb4',
            TEST_DB_USER,
            TEST_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

/** @var resource|null */
$GLOBALS['smoke_server_process'] = null;

function smoke_start_server(): void
{
    echo "Starting PHP dev server on port " . TEST_PORT . "...\n";

    $env = array_merge($_ENV, [
        'DB_HOST' => TEST_DB_HOST,
        'DB_NAME' => TEST_DB_NAME,
        'DB_USER' => TEST_DB_USER,
        'DB_PASS' => TEST_DB_PASS,
    ]);

    // The `exec` prefix matters: without it, proc_open runs the command via
    // an intermediate `/bin/sh -c`, and proc_terminate() below only kills
    // that shell wrapper -- the actual `php -S` process it spawned is
    // orphaned and keeps running. `exec` makes the shell replace itself
    // with the php process instead of forking it, so there's no wrapper
    // left behind to kill incorrectly. (Found by actually checking `ps`
    // after a full test run instead of assuming teardown worked.)
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open(
        'exec php -S 127.0.0.1:' . TEST_PORT,
        $descriptors,
        $pipes,
        __DIR__ . '/..',
        $env
    );

    if (!is_resource($process)) {
        fwrite(STDERR, "Could not start the PHP dev server.\n");
        exit(1);
    }

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $GLOBALS['smoke_server_process'] = $process;

    for ($i = 0; $i < 40; $i++) {
        $ch = curl_init(TEST_BASE_URL . '/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 300);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code > 0) {
            return;
        }
        usleep(150000);
    }

    fwrite(STDERR, "Server never came up.\n");
    exit(1);
}

function smoke_stop_server(): void
{
    if (is_resource($GLOBALS['smoke_server_process'] ?? null)) {
        proc_terminate($GLOBALS['smoke_server_process']);
        proc_close($GLOBALS['smoke_server_process']);
    }
}

/**
 * Tiny curl-based HTTP client with an in-memory cookie jar, so tests can
 * log in once and stay authenticated across requests like a real browser.
 */
function smoke_request(string $method, string $path, array $data = []): array
{
    static $cookieJar = null;
    if ($cookieJar === null) {
        $cookieJar = tempnam(sys_get_temp_dir(), 'smoke_cookies_');
    }

    $ch = curl_init(TEST_BASE_URL . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    return ['status' => $status, 'body' => (string) $body, 'location' => $location];
}

/** Pulls the csrf_token value out of a page's first hidden input -- avoids hardcoding form field order. */
function smoke_extract_csrf(string $html): ?string
{
    if (preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m)) {
        return $m[1];
    }
    return null;
}

// ---------- Assertion / reporting harness ----------

$GLOBALS['smoke_pass'] = 0;
$GLOBALS['smoke_fail'] = 0;
$GLOBALS['smoke_failures'] = [];

function smoke_check(bool $condition, string $label): void
{
    if ($condition) {
        $GLOBALS['smoke_pass']++;
        echo "  \033[32m✓\033[0m $label\n";
    } else {
        $GLOBALS['smoke_fail']++;
        $GLOBALS['smoke_failures'][] = $label;
        echo "  \033[31m✗\033[0m $label\n";
    }
}

function smoke_section(string $title): void
{
    echo "\n\033[1m$title\033[0m\n";
}

function smoke_summary(): int
{
    $pass = $GLOBALS['smoke_pass'];
    $fail = $GLOBALS['smoke_fail'];
    echo "\n" . str_repeat('-', 40) . "\n";
    if ($fail === 0) {
        echo "\033[32mAll $pass checks passed.\033[0m\n";
        return 0;
    }
    echo "\033[31m$fail of " . ($pass + $fail) . " checks failed:\033[0m\n";
    foreach ($GLOBALS['smoke_failures'] as $f) {
        echo "  - $f\n";
    }
    return 1;
}
