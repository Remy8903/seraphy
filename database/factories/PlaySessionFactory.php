<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Client;
use App\Models\PlaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlaySession>
 */
class PlaySessionFactory extends Factory
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
            'activity_id' => Activity::factory(),
            'started_at' => now()->subMinutes(15),
            'ended_at' => null,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the session has been ended.
     */
    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
        ]);
    }
}
