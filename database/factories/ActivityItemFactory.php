<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityItem;
use App\Models\ContentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityItem>
 */
class ActivityItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'content_item_id' => ContentItem::factory()->starter(),
            'paired_item_id' => null,
            'position' => 0,
        ];
    }
}
