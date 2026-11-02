<?php

namespace App\Services\Therapy;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\TrialResult;
use App\Models\Activity;
use App\Models\ActivityItem;
use Illuminate\Validation\Rule;

/**
 * Validation rules and storage format for answers recorded in an activity.
 *
 * Word games answer a word, story sequences a story, follow directions a direction,
 * and balloon voice answers none of them (each answer is one try).
 */
class TrialRules
{
    public function __construct(private Activity $activity) {}

    /**
     * Get the rules for a list of at most `$max` answers under the given key.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(string $key = 'trials', int $max = 100): array
    {
        $gameType = $this->activity->game_type;
        $wordIds = $this->wordIds();

        return [
            $key => ['required', 'array', 'min:1', "max:{$max}"],
            "{$key}.*.client_uuid" => ['required', 'uuid', 'distinct'],
            "{$key}.*.content_item_id" => $this->answersWords($gameType)
                ? ['required', 'integer', Rule::in($wordIds)]
                : ['prohibited'],
            "{$key}.*.group_number" => $gameType->usesStories()
                ? ['required', 'integer', Rule::in($this->storyNumbers())]
                : ['prohibited'],
            "{$key}.*.activity_direction_id" => $gameType->usesDirections()
                ? ['required', 'integer', Rule::in($this->activity->directions->modelKeys())]
                : ['prohibited'],
            "{$key}.*.result" => ['required', Rule::enum(TrialResult::class)],
            "{$key}.*.response_ms" => ['nullable', 'integer', 'between:0,600000'],
            "{$key}.*.meta" => ['nullable', 'array:attempts,chosen_item_id,chosen_category,touched_item_ids,held_ms,average_level'],
            "{$key}.*.meta.attempts" => ['integer', 'between:1,50'],
            "{$key}.*.meta.chosen_item_id" => ['integer', Rule::in($wordIds)],
            "{$key}.*.meta.chosen_category" => ['string', Rule::in($this->activity->settings['categories'] ?? [])],
            "{$key}.*.meta.touched_item_ids" => ['array', 'max:8'],
            "{$key}.*.meta.touched_item_ids.*" => ['integer', Rule::in($wordIds)],
            "{$key}.*.meta.held_ms" => ['integer', 'between:0,600000'],
            "{$key}.*.meta.average_level" => ['numeric', 'between:0,100'],
        ];
    }

    /**
     * Turn validated answers into trial rows, with each meta value in its stored type.
     *
     * @param  array<int, array<string, mixed>>  $trials
     * @return list<array{client_uuid: string, content_item_id: int|null, group_number: int|null, activity_direction_id: int|null, result: string, response_ms: int|null, meta: string|null}>
     */
    public static function attributes(array $trials): array
    {
        return array_map(fn (array $trial): array => [
            'client_uuid' => $trial['client_uuid'],
            'content_item_id' => isset($trial['content_item_id']) ? (int) $trial['content_item_id'] : null,
            'group_number' => isset($trial['group_number']) ? (int) $trial['group_number'] : null,
            'activity_direction_id' => isset($trial['activity_direction_id']) ? (int) $trial['activity_direction_id'] : null,
            'result' => $trial['result'],
            'response_ms' => isset($trial['response_ms']) ? (int) $trial['response_ms'] : null,
            'meta' => empty($trial['meta']) ? null : json_encode(self::normalizedMeta($trial['meta'])),
        ], array_values($trials));
    }

    /**
     * Cast each meta value to the type it is stored as.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private static function normalizedMeta(array $meta): array
    {
        return collect($meta)->map(fn (mixed $value, string $key): mixed => match ($key) {
            'chosen_category' => (string) $value,
            'touched_item_ids' => array_map(intval(...), array_values($value)),
            'average_level' => round((float) $value, 2),
            default => (int) $value,
        })->all();
    }

    /**
     * Determine whether each answer in the game is for a single word.
     */
    private function answersWords(GameType $gameType): bool
    {
        return $gameType->usesWords() && ! $gameType->usesStories() && ! $gameType->usesDirections();
    }

    /**
     * Get the ids of every word that can appear in the activity.
     *
     * @return list<int>
     */
    private function wordIds(): array
    {
        $wordIds = $this->activity->items
            ->flatMap(fn (ActivityItem $item): array => array_filter([$item->content_item_id, $item->paired_item_id]))
            ->unique()
            ->all();

        return array_values($wordIds);
    }

    /**
     * Get the numbers of the stories in the activity.
     *
     * @return list<int>
     */
    private function storyNumbers(): array
    {
        return $this->activity->items->pluck('group_number')->filter()->unique()->values()->all();
    }
}
