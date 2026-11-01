<?php

namespace Database\Factories;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use App\Models\ClinicalLogEntry;
use App\Models\ClinicalLogForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalLogEntry>
 */
class ClinicalLogEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinical_log_form_id' => ClinicalLogForm::factory(),
            'date' => fake()->dateTimeBetween('-1 year'),
            'age_group' => fake()->randomElement(AgeGroup::cases()),
            'rn_no' => fake()->optional()->bothify('RN####'),
            'session_mode' => fake()->randomElement(SessionMode::cases()),
            'session_type' => fake()->randomElement(SessionType::cases()),
            'diagnosis' => fake()->randomElement(['Speech sound disorder', 'Autism spectrum disorder', 'Aphasia', 'Stuttering', 'Dysphagia']),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
        ];
    }
}
