<?php

namespace Database\Factories;

use App\Enums\ClinicalHours\ClinicMode;
use App\Enums\ClinicalHours\HoursType;
use App\Enums\ClinicalHours\LogMode;
use App\Enums\ClinicalHours\PlacementType;
use App\Enums\ClinicalHours\PracticumType;
use App\Enums\ClinicalHours\WeekDay;
use App\Models\ClinicalLogForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalLogForm>
 */
class ClinicalLogFormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mode' => LogMode::LogForm,
            'year' => fake()->numberBetween(2, 4),
            'semester' => fake()->numberBetween(1, 2),
            'session_start_year' => fake()->numberBetween(2021, 2026),
            'days' => [fake()->randomElement(WeekDay::cases())->value],
            'placement' => fake()->randomElement(PlacementType::cases()),
            'clinic_mode' => fake()->randomElement(ClinicMode::cases()),
            'placement_name' => fake()->company(),
            'supervisor_name' => fake()->name(),
            'practicum_type' => PracticumType::StudentBlock,
            'hours_type' => fake()->randomElement(HoursType::cases()),
        ];
    }

    /**
     * Indicate that the form logs observation hours.
     */
    public function observation(): static
    {
        return $this->state(fn (array $attributes) => [
            'hours_type' => HoursType::Observation,
        ]);
    }

    /**
     * Indicate that the form logs hands-on hours.
     */
    public function handsOn(): static
    {
        return $this->state(fn (array $attributes) => [
            'hours_type' => HoursType::HandsOn,
        ]);
    }

    /**
     * Indicate that the form logs Latihan Industri (internship) hours.
     */
    public function internship(): static
    {
        return $this->state(fn (array $attributes) => [
            'practicum_type' => PracticumType::LatihanIndustri,
        ]);
    }

    /**
     * Indicate that the hours were keyed in directly in simple mode.
     */
    public function simple(int $minutes = 120): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => LogMode::Simple,
            'days' => null,
            'placement' => null,
            'clinic_mode' => null,
            'placement_name' => null,
            'supervisor_name' => null,
            'simple_minutes' => $minutes,
        ]);
    }
}
