<?php

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function test_required_fails_on_empty_string(): void
    {
        $v = new Validator(['name' => '']);
        $v->required('name', 'Name');

        $this->assertTrue($v->fails());
        $this->assertSame('Name is required.', $v->firstError());
    }

    public function test_required_fails_on_whitespace_only(): void
    {
        $v = new Validator(['name' => '   ']);
        $v->required('name', 'Name');

        $this->assertTrue($v->fails());
    }

    public function test_required_fails_on_missing_key(): void
    {
        $v = new Validator([]);
        $v->required('name', 'Name');

        $this->assertTrue($v->fails());
    }

    public function test_required_passes_with_value(): void
    {
        $v = new Validator(['name' => 'Bella']);
        $v->required('name', 'Name');

        $this->assertFalse($v->fails());
    }

    public function test_email_rejects_invalid_address(): void
    {
        $v = new Validator(['email' => 'not-an-email']);
        $v->email('email');

        $this->assertTrue($v->fails());
    }

    public function test_email_accepts_valid_address(): void
    {
        $v = new Validator(['email' => 'admin@afrisap.test']);
        $v->email('email');

        $this->assertFalse($v->fails());
    }

    public function test_email_skips_empty_value(): void
    {
        // email() should not fire on an empty/optional field -- required() handles presence separately.
        $v = new Validator(['email' => '']);
        $v->email('email');

        $this->assertFalse($v->fails());
    }

    public function test_min_length_rejects_short_value(): void
    {
        $v = new Validator(['password' => 'short']);
        $v->minLength('password', 8, 'Password');

        $this->assertTrue($v->fails());
        $this->assertSame('Password must be at least 8 characters.', $v->firstError());
    }

    public function test_min_length_accepts_long_enough_value(): void
    {
        $v = new Validator(['password' => 'longenough']);
        $v->minLength('password', 8, 'Password');

        $this->assertFalse($v->fails());
    }

    public function test_numeric_rejects_non_numeric_string(): void
    {
        $v = new Validator(['amount' => 'abc']);
        $v->numeric('amount', 'Amount');

        $this->assertTrue($v->fails());
    }

    public function test_numeric_accepts_decimal_string(): void
    {
        $v = new Validator(['amount' => '1234.56']);
        $v->numeric('amount', 'Amount');

        $this->assertFalse($v->fails());
    }

    public function test_numeric_skips_empty_string(): void
    {
        // Optional numeric fields (e.g. budget) submitted blank should not fail validation.
        $v = new Validator(['amount' => '']);
        $v->numeric('amount', 'Amount');

        $this->assertFalse($v->fails());
    }

    public function test_in_rejects_value_outside_allowed_set(): void
    {
        $v = new Validator(['status' => 'bogus']);
        $v->in('status', ['active', 'inactive'], 'Status');

        $this->assertTrue($v->fails());
    }

    public function test_in_accepts_value_within_allowed_set(): void
    {
        $v = new Validator(['status' => 'active']);
        $v->in('status', ['active', 'inactive'], 'Status');

        $this->assertFalse($v->fails());
    }

    public function test_chained_rules_accumulate_errors_for_multiple_fields(): void
    {
        $v = new Validator(['name' => '', 'email' => 'bad']);
        $v->required('name', 'Name')->email('email');

        $errors = $v->errors();
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_first_error_returns_null_when_no_errors(): void
    {
        $v = new Validator(['name' => 'Bella']);
        $v->required('name', 'Name');

        $this->assertNull($v->firstError());
    }
}
