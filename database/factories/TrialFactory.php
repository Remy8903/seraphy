<?php

namespace Database\Factories;

use App\Enums\Therapy\TrialResult;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\Trial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trial>
 */
class TrialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'play_session_id' => PlaySession::factory(),
            'content_item_id' => ContentItem::factory()->starter(),
            'result' => fake()->randomElement(TrialResult::cases()),
            'response_ms' => fake()->numberBetween(500, 5000),
            'client_uuid' => fake()->uuid(),
        ];
    }

    /**
     * Indicate that the answer was correct.
     */
    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => TrialResult::Correct,
        ]);
    }

    /**
     * Indicate that the answer was correct after a prompt.
     */
    public function prompted(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => TrialResult::Prompted,
        ]);
    }

    /**
     * Indicate that the answer was incorrect.
     */
    public function incorrect(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => TrialResult::Incorrect,
        ]);
    }
}
