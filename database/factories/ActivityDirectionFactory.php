<?php

namespace Database\Factories;

use App\Enums\Therapy\GameType;
use App\Models\Activity;
use App\Models\ActivityDirection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityDirection>
 */
class ActivityDirectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory()->state(['game_type' => GameType::FollowDirections]),
            'text' => 'Sentuh bola',
            'item_ids' => [],
            'position' => 0,
        ];
    }
}
