<?php

use PHPUnit\Framework\TestCase;

final class FunctionsTest extends TestCase
{
    public function test_slugify_normalizes_text(): void
    {
        $this->assertSame('green-valley-farms', slugify('Green Valley Farms'));
        $this->assertSame('a-b-c', slugify('  A!!  B__C  '));
        $this->assertSame('', slugify('###'));
    }

    public function test_humanize_converts_snake_case(): void
    {
        $this->assertSame('Field Worker', humanize('field_worker'));
        $this->assertSame('In Progress', humanize('in_progress'));
    }

    public function test_format_money_formats_two_decimals(): void
    {
        $this->assertSame('USD 1,234.50', format_money(1234.5));
        $this->assertSame('USD 0.00', format_money(null));
    }

    public function test_format_date_handles_null_and_invalid(): void
    {
        $this->assertSame('-', format_date(null));
        $this->assertSame('01 Jan 2026', format_date('2026-01-01'));
    }

    public function test_status_badge_class_maps_known_statuses(): void
    {
        $this->assertSame('badge-success', status_badge_class('active'));
        $this->assertSame('badge-warning', status_badge_class('pending'));
        $this->assertSame('badge-danger', status_badge_class('suspended'));
        $this->assertSame('badge-secondary', status_badge_class('something_unknown'));
    }

    public function test_sanitize_filename_strips_unsafe_characters(): void
    {
        $this->assertSame('my_file_name.jpg', sanitize_filename('my file/name.jpg'));
    }

    public function test_generate_batch_id_has_expected_shape(): void
    {
        $id = generate_batch_id('CROP');
        $this->assertMatchesRegularExpression('/^CROP-\d{8}-[A-F0-9]{6}$/', $id);
    }
}
