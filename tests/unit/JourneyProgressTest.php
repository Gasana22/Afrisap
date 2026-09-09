<?php

use PHPUnit\Framework\TestCase;

final class JourneyProgressTest extends TestCase
{
    private function render(array $journey): string
    {
        ob_start();
        render_journey_progress($journey);
        return ob_get_clean();
    }

    public function test_renders_one_step_per_canonical_stage(): void
    {
        $html = $this->render([]);
        $this->assertSame(count(TRACE_JOURNEY_STAGES), substr_count($html, 'stage-step'));
        $this->assertSame(count(TRACE_JOURNEY_STAGES) - 1, substr_count($html, 'stage-line'));
    }

    public function test_matched_stage_is_marked_done_with_checkmark(): void
    {
        $html = $this->render([
            ['stage' => 'Harvest', 'start_date' => '2026-01-10'],
        ]);

        $this->assertStringContainsString('stage-step done', $html);
        $this->assertStringContainsString('bi-check-lg', $html);
        // Only one of the nine stages was recorded, so only one should be done.
        $this->assertSame(1, substr_count($html, 'stage-step done'));
    }

    public function test_stage_matching_is_case_and_whitespace_insensitive(): void
    {
        $html = $this->render([
            ['stage' => '  SEED  ', 'start_date' => null],
        ]);

        $this->assertSame(1, substr_count($html, 'stage-step done'));
    }

    public function test_unrecognized_stage_names_are_not_marked_done(): void
    {
        // Matches the real demo seed data, whose stage names ("Planting")
        // don't match the canonical vocabulary - the progress bar should
        // just show everything as upcoming rather than erroring or
        // misattributing the match.
        $html = $this->render([
            ['stage' => 'Planting', 'start_date' => '2026-01-05'],
        ]);

        $this->assertSame(0, substr_count($html, 'stage-step done'));
    }

    public function test_no_journey_still_renders_all_stages_as_upcoming(): void
    {
        $html = $this->render([]);
        $this->assertSame(0, substr_count($html, 'stage-step done'));
        $this->assertStringContainsString('>1<', $html);
    }
}
