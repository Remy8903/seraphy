<?php

namespace App\Http\Requests;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\Language;
use App\Enums\Therapy\StickerSet;
use App\Enums\Therapy\VoiceLevel;
use App\Models\Activity;
use App\Models\ContentItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class ActivityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $gameType = $this->gameType();
        $usesWords = $gameType?->usesWords() ?? true;
        $usesPairs = $gameType?->usesPairs() ?? false;
        $usesStories = $gameType?->usesStories() ?? false;
        $usesCategories = $gameType?->usesCategories() ?? false;
        $usesDirections = $gameType?->usesDirections() ?? false;
        $isFindIt = $gameType === GameType::FindIt;
        $isBalloonVoice = $gameType === GameType::BalloonVoice;
        [$minItems, $maxItems] = $this->itemLimits();

        return [
            'game_type' => ['required', Rule::enum(GameType::class)],
            'title' => ['required', 'string', 'max:100'],
            'language' => ['required', Rule::enum(Language::class)],
            'settings' => ['required', 'array'],
            'settings.trial_count' => ['required', 'integer', 'between:1,100'],
            'settings.shuffle' => ['required', 'boolean'],
            'settings.show_word_text' => ['required', 'boolean'],
            'settings.reward_every' => ['sometimes', 'integer', 'between:0,20'],
            'settings.sticker_set' => [
                Rule::requiredIf(fn (): bool => (int) $this->input('settings.reward_every', 0) > 0),
                'nullable',
                Rule::enum(StickerSet::class),
            ],
            'settings.field_size' => $isFindIt ? ['required', 'integer', 'between:3,6'] : ['exclude'],
            'settings.speak_word' => $isFindIt || $usesDirections ? ['required', 'boolean'] : ['exclude'],
            'settings.categories' => $usesCategories ? ['required', 'array', 'min:2', 'max:3'] : ['exclude'],
            'settings.categories.*' => $usesCategories ? ['required', 'string', 'max:50', 'distinct'] : ['exclude'],
            'settings.target_level' => $isBalloonVoice ? ['required', Rule::enum(VoiceLevel::class)] : ['exclude'],
            'settings.hold_seconds' => $isBalloonVoice ? ['required', 'integer', 'between:1,10'] : ['exclude'],
            'background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'remove_background' => ['boolean'],
            'items' => $usesWords ? ['required', 'array', "min:{$minItems}", "max:{$maxItems}"] : ['exclude'],
            'items.*.content_item_id' => match (true) {
                ! $usesWords => ['exclude'],
                $usesStories => ['required', 'integer', $this->usableWord()],
                default => ['required', 'integer', 'distinct', $this->usableWord()],
            },
            'items.*.group_number' => $usesStories ? ['required', 'integer', 'between:1,100'] : ['exclude'],
            'items.*.paired_item_id' => $usesPairs
                ? ['required', 'integer', 'different:items.*.content_item_id', $this->usableWord()]
                : ['exclude'],
            'directions' => $usesDirections ? ['required', 'array', 'min:1', 'max:30'] : ['exclude'],
            'directions.*.id' => $usesDirections && $this->activity()
                ? ['nullable', 'integer', 'distinct', Rule::exists('activity_directions', 'id')->where('activity_id', $this->activity()->id)]
                : ['exclude'],
            'directions.*.text' => $usesDirections ? ['required', 'string', 'max:150'] : ['exclude'],
            'directions.*.item_ids' => $usesDirections ? ['required', 'array', 'min:1', 'max:4'] : ['exclude'],
            'directions.*.item_ids.*' => $usesDirections
                ? ['required', 'integer', Rule::in(array_map(intval(...), array_column((array) $this->input('items', []), 'content_item_id')))]
                : ['exclude'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $itemsMessage = match ($this->gameType()) {
            GameType::FindIt => 'Add at least 3 words so the child has pictures to choose from.',
            GameType::FollowDirections => 'Put 4 to 8 pictures on the board.',
            default => 'Add at least one word to the activity.',
        };

        return [
            'items.required' => $itemsMessage,
            'items.min' => $itemsMessage,
            'items.max' => $itemsMessage,
            'items.*.content_item_id.distinct' => 'Each word can only be added once.',
            'items.*.content_item_id.exists' => 'Choose words from your library in the same language as the activity.',
            'items.*.paired_item_id.required' => 'Choose the contrasting word for each pair.',
            'items.*.paired_item_id.exists' => 'Choose words from your library in the same language as the activity.',
            'settings.sticker_set.required' => 'Choose a sticker set, or turn stickers off.',
            'settings.categories.required' => 'Choose 2 or 3 groups to sort into.',
            'settings.categories.min' => 'Choose 2 or 3 groups to sort into.',
            'settings.categories.max' => 'Choose 2 or 3 groups to sort into.',
            'directions.required' => 'Add at least one direction.',
            'directions.*.text.required' => 'Write what you will say for each direction.',
            'directions.*.item_ids.required' => 'Choose the pictures to touch for each direction.',
            'directions.*.item_ids.max' => 'A direction can have up to 4 steps.',
            'directions.*.item_ids.*.in' => 'Directions can only use pictures on the board.',
            'background.max' => 'The background picture must be 5 MB or smaller.',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                match ($this->gameType()) {
                    GameType::StorySequence => $this->validateStories($validator),
                    GameType::SortIt => $this->validateSortingGroups($validator),
                    GameType::FollowDirections => $this->validateDirections($validator),
                    default => null,
                };
            },
        ];
    }

    /**
     * Get the validated activity attributes.
     *
     * @return array<string, mixed>
     */
    public function activityAttributes(): array
    {
        $gameType = $this->gameType();
        $rewardEvery = (int) $this->validated('settings.reward_every', 0);

        return [
            ...$this->safe()->only(['game_type', 'title', 'language']),
            'settings' => [
                'trial_count' => (int) $this->validated('settings.trial_count'),
                'shuffle' => (bool) $this->validated('settings.shuffle'),
                'show_word_text' => (bool) $this->validated('settings.show_word_text'),
                'reward_every' => $rewardEvery,
                ...($rewardEvery > 0 ? ['sticker_set' => $this->validated('settings.sticker_set')] : []),
                ...($gameType === GameType::FindIt ? [
                    'field_size' => (int) $this->validated('settings.field_size'),
                ] : []),
                ...($gameType === GameType::FindIt || $gameType === GameType::FollowDirections ? [
                    'speak_word' => (bool) $this->validated('settings.speak_word'),
                ] : []),
                ...($gameType === GameType::SortIt ? [
                    'categories' => array_values($this->validated('settings.categories')),
                ] : []),
                ...($gameType === GameType::BalloonVoice ? [
                    'target_level' => $this->validated('settings.target_level'),
                    'hold_seconds' => (int) $this->validated('settings.hold_seconds'),
                ] : []),
            ],
        ];
    }

    /**
     * Get the validated words in the order they were added.
     *
     * @return list<array{content_item_id: int, paired_item_id: int|null, group_number: int|null, position: int}>
     */
    public function itemAttributes(): array
    {
        $items = array_values($this->validated('items', []));

        return array_map(
            fn (array $item, int $position): array => [
                'content_item_id' => (int) $item['content_item_id'],
                'paired_item_id' => isset($item['paired_item_id']) ? (int) $item['paired_item_id'] : null,
                'group_number' => isset($item['group_number']) ? (int) $item['group_number'] : null,
                'position' => $position,
            ],
            $items,
            array_keys($items),
        );
    }

    /**
     * Get the validated directions in order, with the id of each existing direction.
     *
     * @return list<array{id: int|null, text: string, item_ids: list<int>, position: int}>
     */
    public function directionAttributes(): array
    {
        $directions = array_values($this->validated('directions', []));

        return array_map(
            fn (array $direction, int $position): array => [
                'id' => isset($direction['id']) ? (int) $direction['id'] : null,
                'text' => $direction['text'],
                'item_ids' => array_map(intval(...), array_values($direction['item_ids'])),
                'position' => $position,
            ],
            $directions,
            array_keys($directions),
        );
    }

    /**
     * Require each story to have 3 to 5 different cards.
     */
    private function validateStories(Validator $validator): void
    {
        $stories = collect($this->input('items', []))->groupBy('group_number');

        foreach ($stories as $cards) {
            if ($cards->count() < 3 || $cards->count() > 5) {
                $validator->errors()->add('items', 'Each story needs 3 to 5 cards.');

                return;
            }

            if ($cards->pluck('content_item_id')->duplicates()->isNotEmpty()) {
                $validator->errors()->add('items', 'A card can only appear once in each story.');

                return;
            }
        }
    }

    /**
     * Require every picture to belong to one of the chosen groups, and every group to have a picture.
     */
    private function validateSortingGroups(Validator $validator): void
    {
        $categories = array_values($this->input('settings.categories', []));
        $pictureCategories = ContentItem::query()
            ->whereKey(array_column($this->input('items', []), 'content_item_id'))
            ->pluck('category');

        if ($pictureCategories->contains(fn (?string $category): bool => ! in_array($category, $categories, true))) {
            $validator->errors()->add('items', 'Every picture must belong to one of the groups.');

            return;
        }

        if (array_diff($categories, $pictureCategories->unique()->all()) !== []) {
            $validator->errors()->add('items', 'Add at least one picture to each group.');
        }
    }

    /**
     * Require each direction to touch different pictures.
     */
    private function validateDirections(Validator $validator): void
    {
        foreach (array_values($this->input('directions', [])) as $index => $direction) {
            $itemIds = array_map(intval(...), $direction['item_ids']);

            if (count($itemIds) !== count(array_unique($itemIds))) {
                $validator->errors()->add("directions.{$index}.item_ids", 'Each picture can only be touched once in a direction.');
            }
        }
    }

    /**
     * Get the fewest and most words the chosen game allows.
     *
     * @return array{0: int, 1: int}
     */
    private function itemLimits(): array
    {
        return match ($this->gameType()) {
            GameType::FindIt => [3, 100],
            GameType::FollowDirections => [4, 8],
            default => [1, 100],
        };
    }

    /**
     * Get the chosen game type, if it is valid.
     */
    private function gameType(): ?GameType
    {
        return $this->enum('game_type', GameType::class);
    }

    /**
     * Get the activity being updated, if any.
     */
    private function activity(): ?Activity
    {
        $activity = $this->route('activity');

        return $activity instanceof Activity ? $activity : null;
    }

    /**
     * Require a word the clinician can see, in the activity's language.
     */
    private function usableWord(): Exists
    {
        return Rule::exists('content_items', 'id')
            ->where('language', (string) $this->input('language'))
            ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', $this->user()->id));
    }
}
