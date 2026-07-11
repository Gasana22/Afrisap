<?php

namespace Tests\Integration;

use App\Models\Animal;

class AnimalCodeTest extends DatabaseTestCase
{
    public function test_animal_code_matches_srs_format(): void
    {
        $farmId = $this->createFarm();

        $id = Animal::create([
            'farm_id' => $farmId,
            'name' => 'Bella',
            'species' => 'Cattle',
            'breed' => 'Ankole',
            'tag_number' => 'T-001',
            'gender' => 'female',
            'birth_date' => '2026-01-01',
            'parent_id' => null,
        ]);

        $animal = Animal::find($id);

        // SRS example format: AN-CATTLE-2026-0048
        $this->assertMatchesRegularExpression('/^AN-CATTLE-2026-\d{4}$/', $animal['animal_code']);
    }

    public function test_animal_code_sequence_is_zero_padded_to_four_digits(): void
    {
        $farmId = $this->createFarm();

        $id = Animal::create([
            'farm_id' => $farmId, 'name' => 'Goat 1', 'species' => 'Goat', 'breed' => '',
            'tag_number' => '', 'gender' => 'female', 'birth_date' => '2026-03-01', 'parent_id' => null,
        ]);

        $this->assertStringEndsWith('-0001', Animal::find($id)['animal_code']);
    }

    public function test_animal_code_falls_back_to_current_year_when_birth_date_missing(): void
    {
        $farmId = $this->createFarm();

        $id = Animal::create([
            'farm_id' => $farmId, 'name' => 'Unknown Age', 'species' => 'Sheep', 'breed' => '',
            'tag_number' => '', 'gender' => 'male', 'birth_date' => '', 'parent_id' => null,
        ]);

        $this->assertStringContainsString('-' . date('Y') . '-', Animal::find($id)['animal_code']);
    }
}
