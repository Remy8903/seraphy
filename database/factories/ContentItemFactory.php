<?php

namespace Database\Factories;

use App\Enums\Therapy\Language;
use App\Enums\Therapy\WordPosition;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentItem>
 */
class ContentItemFactory extends Factory
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
            'language' => Language::Malay,
            'word' => fake()->unique()->word(),
            'emoji' => fake()->randomElement(['🐱', '🍎', '🚗', '⚽', '🌸']),
            'image_path' => null,
            'target_sound' => fake()->randomElement(['s', 'k', 't', 'l', 'r']),
            'word_position' => WordPosition::Initial,
            'category' => fake()->randomElement(['haiwan', 'makanan', 'benda']),
        ];
    }

    /**
     * Indicate that the word belongs to the shared starter library.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the word is in English.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => Language::English,
        ]);
    }
}
