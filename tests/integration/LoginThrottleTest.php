<?php

use PHPUnit\Framework\TestCase;

/**
 * Exercises the login throttle (includes/auth.php: login_rate_limited(),
 * record_failed_login(), clear_login_attempts()) against a real database.
 * This guards against a real regression found during manual testing: the
 * cutoff was originally computed with PHP's date() under APP_TIMEZONE
 * (e.g. Africa/Kigali, UTC+2) and compared against created_at, which MySQL
 * stores/returns in its own (UTC) clock - the two-hour skew meant the
 * "created_at > cutoff" comparison was never true, so the limiter silently
 * never triggered. The fix computes the cutoff with MySQL's own NOW().
 */
final class LoginThrottleTest extends TestCase
{
    private string $email;

    protected function setUp(): void
    {
        if (!test_db_available()) {
            $this->markTestSkipped('No database configured for this environment.');
        }

        require_once __DIR__ . '/../../includes/db.php';
        require_once __DIR__ . '/../../includes/auth.php';

        $this->email = 'throttle-test-' . uniqid() . '@example.com';
    }

    protected function tearDown(): void
    {
        if (!test_db_available()) {
            return;
        }

        db_delete('login_attempts', 'email = :email', ['email' => $this->email]);
    }

    public function test_not_rate_limited_with_no_recorded_attempts(): void
    {
        $this->assertFalse(login_rate_limited($this->email));
    }

    public function test_becomes_rate_limited_after_max_attempts(): void
    {
        $config = require __DIR__ . '/../../config/auth.php';

        for ($i = 0; $i < $config['max_login_attempts']; $i++) {
            $this->assertFalse(login_rate_limited($this->email), "Should not be limited before attempt $i.");
            record_failed_login($this->email);
        }

        $this->assertTrue(login_rate_limited($this->email));
    }

    public function test_clear_login_attempts_resets_the_throttle(): void
    {
        record_failed_login($this->email);
        record_failed_login($this->email);
        clear_login_attempts($this->email);

        $this->assertFalse(login_rate_limited($this->email));
    }

    public function test_recorded_attempt_created_at_is_within_the_lockout_window(): void
    {
        // Regression check for the timezone-skew bug: a row inserted "now"
        // must count as within the lockout window right away, not appear
        // to be hours in the past (or future) relative to the cutoff.
        record_failed_login($this->email);

        $config = require __DIR__ . '/../../config/auth.php';
        $count = (int) db_value(
            "SELECT COUNT(*) FROM login_attempts WHERE email = :email AND created_at > (NOW() - INTERVAL {$config['lockout_minutes']} MINUTE)",
            ['email' => $this->email]
        );

        $this->assertSame(1, $count);
    }
}
