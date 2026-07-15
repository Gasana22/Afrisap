<?php

use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function test_required_field_fails_when_missing(): void
    {
        $errors = validate([], ['name' => 'required']);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_email_rule_rejects_invalid_address(): void
    {
        $errors = validate(['email' => 'not-an-email'], ['email' => 'required|email']);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_email_rule_accepts_valid_address(): void
    {
        $errors = validate(['email' => 'owner@greenvalley.test'], ['email' => 'required|email']);
        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_numeric_rule(): void
    {
        $errors = validate(['qty' => 'abc'], ['qty' => 'numeric']);
        $this->assertArrayHasKey('qty', $errors);

        $errors = validate(['qty' => '12.5'], ['qty' => 'numeric']);
        $this->assertArrayNotHasKey('qty', $errors);
    }

    public function test_min_and_max_length(): void
    {
        $errors = validate(['password' => 'short'], ['password' => 'min:8']);
        $this->assertArrayHasKey('password', $errors);

        $errors = validate(['password' => 'longenough'], ['password' => 'min:8']);
        $this->assertArrayNotHasKey('password', $errors);
    }

    public function test_in_rule_restricts_to_allowed_values(): void
    {
        $errors = validate(['status' => 'bogus'], ['status' => 'in:active,inactive']);
        $this->assertArrayHasKey('status', $errors);

        $errors = validate(['status' => 'active'], ['status' => 'in:active,inactive']);
        $this->assertArrayNotHasKey('status', $errors);
    }

    public function test_validation_failed_helper(): void
    {
        $this->assertTrue(validation_failed(['field' => 'error']));
        $this->assertFalse(validation_failed([]));
    }
}
