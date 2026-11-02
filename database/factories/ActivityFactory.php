<?php

namespace Database\Factories;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\Language;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
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
            'game_type' => GameType::SoundDrill,
            'title' => fake()->words(3, true),
            'language' => Language::Malay,
            'settings' => [
                'trial_count' => 10,
                'shuffle' => true,
                'show_word_text' => false,
            ],
        ];
    }

    /**
     * Indicate that the activity is a minimal pairs game.
     */
    public function minimalPairs(): static
    {
        return $this->state(fn (array $attributes) => [
            'game_type' => GameType::MinimalPairs,
        ]);
    }
}
