<?php

namespace Database\Factories;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\Therapy\Language;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'reference_code' => 'C-'.fake()->unique()->numerify('####'),
            'age_group' => AgeGroup::ChildAdolescent,
            'language' => fake()->randomElement(Language::cases()),
            'diagnosis' => fake()->randomElement(['Speech sound disorder', 'Language delay', null]),
            'notes' => null,
        ];
    }

    /**
     * Indicate that the client has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
