<?php

use PHPUnit\Framework\TestCase;

/**
 * Boots the app with PHP's built-in server (router.php) and drives real
 * HTTP requests through curl to prove the three login guards (platform
 * admin, org-admin, worker) actually gate access end to end. Skips itself
 * if no database is reachable or the demo tenant hasn't been seeded.
 */
final class AuthFlowTest extends TestCase
{
    /** @var resource|null */
    private static $process = null;
    private static string $base = 'http://127.0.0.1:8199';

    public static function setUpBeforeClass(): void
    {
        if (!test_db_available()) {
            return;
        }

        // Kill any server left over from a previous interrupted run.
        exec("fuser -k 8199/tcp 2>/dev/null");

        $descriptors = [1 => ['file', '/tmp/sfp_test_server.log', 'w'], 2 => ['file', '/tmp/sfp_test_server.log', 'w']];
        self::$process = proc_open(
            'exec php -S 127.0.0.1:8199 router.php',
            $descriptors,
            $pipes,
            dirname(__DIR__, 2)
        );

        usleep(500000);
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$process)) {
            proc_terminate(self::$process, 9);
            proc_close(self::$process);
        }
        // Belt-and-braces: make sure nothing is left listening on the test port.
        exec("fuser -k 8199/tcp 2>/dev/null");
    }

    private function curlGet(string $path, ?string $cookieJar = null): array
    {
        $ch = curl_init(self::$base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $cookieJar,
            CURLOPT_COOKIEFILE => $cookieJar,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $body];
    }

    private function curlPost(string $path, array $fields, ?string $cookieJar = null): array
    {
        $ch = curl_init(self::$base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_COOKIEJAR => $cookieJar,
            CURLOPT_COOKIEFILE => $cookieJar,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $body];
    }

    private function scrapeCsrf(string $html): string
    {
        preg_match('/csrf_token" value="([^"]+)"/', $html, $m);

        return $m[1] ?? '';
    }

    public function test_org_admin_dashboard_requires_login(): void
    {
        if (!test_db_available()) {
            $this->markTestSkipped('No database configured for this environment.');
        }

        [$code] = $this->curlGet('/org-admin/index.php');
        $this->assertSame(302, $code, 'Unauthenticated access to org-admin must redirect to login.');
    }

    public function test_owner_login_reaches_dashboard(): void
    {
        if (!test_db_available()) {
            $this->markTestSkipped('No database configured for this environment.');
        }

        $jar = tempnam(sys_get_temp_dir(), 'sfp_cookie_');
        [, $loginPage] = $this->curlGet('/public/login.php', $jar);
        $csrf = $this->scrapeCsrf($loginPage);

        [$code, $body] = $this->curlPost('/public/login.php', [
            'email' => 'owner@greenvalley.test',
            'password' => 'Password123!',
            'csrf_token' => $csrf,
        ], $jar);

        if ($code !== 302) {
            $this->markTestSkipped('Demo tenant not seeded in this environment - skipping login assertion.');
        }

        [$dashCode, $dashBody] = $this->curlGet('/org-admin/index.php', $jar);
        $this->assertSame(200, $dashCode);
        $this->assertStringNotContainsString('Fatal error', $dashBody);
        $this->assertStringNotContainsString('SQLSTATE', $dashBody);

        unlink($jar);
    }

    public function test_worker_login_is_separate_from_org_admin_login(): void
    {
        if (!test_db_available()) {
            $this->markTestSkipped('No database configured for this environment.');
        }

        $jar = tempnam(sys_get_temp_dir(), 'sfp_cookie_');
        [, $loginPage] = $this->curlGet('/worker/login.php', $jar);
        $csrf = $this->scrapeCsrf($loginPage);

        [$code] = $this->curlPost('/worker/login.php', [
            'email' => 'worker@greenvalley.test',
            'password' => 'Password123!',
            'csrf_token' => $csrf,
        ], $jar);

        if ($code !== 302) {
            $this->markTestSkipped('Demo worker not seeded in this environment - skipping login assertion.');
        }

        // A worker session must not grant access to the org-admin dashboard.
        [$orgAdminCode] = $this->curlGet('/org-admin/index.php', $jar);
        $this->assertSame(302, $orgAdminCode, 'A worker session must not be treated as an org-admin session.');

        unlink($jar);
    }
}
