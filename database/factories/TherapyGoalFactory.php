<?php

namespace Database\Factories;

use App\Enums\Therapy\GoalStatus;
use App\Models\Client;
use App\Models\TherapyGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TherapyGoal>
 */
class TherapyGoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'description' => fake()->sentence(),
            'game_type' => null,
            'language' => null,
            'target_sound' => null,
            'word_position' => null,
            'target_accuracy' => 80,
            'consecutive_sessions' => 3,
            'min_trials_per_session' => 10,
            'status' => GoalStatus::Active,
            'achieved_at' => null,
        ];
    }

    /**
     * Indicate that the goal has been reached.
     */
    public function achieved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoalStatus::Achieved,
            'achieved_at' => now(),
        ]);
    }

    /**
     * Indicate that the clinician stopped working on the goal.
     */
    public function discontinued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoalStatus::Discontinued,
        ]);
    }
}
