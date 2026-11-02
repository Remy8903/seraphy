<?php

namespace App\Services\Therapy;

use App\Enums\Therapy\StickerSet;
use App\Models\Activity;
use App\Models\ActivityDirection;
use App\Models\ActivityItem;

class GameContent
{
    /**
     * Get everything the game screen needs to play an activity: its settings, pictures,
     * directions and stickers. Used online and when saving an activity for offline play.
     *
     * @return array{
     *     activity: array{id: int, title: string, game_type: string, language: string, settings: array<string, mixed>, background_url: string|null},
     *     cards: list<array{word: array{id: int, word: string, emoji: string|null, image_url: string|null}, pair: array{id: int, word: string, emoji: string|null, image_url: string|null}|null, group_number: int|null, category: string|null}>,
     *     directions: list<array{id: int, text: string, item_ids: list<int>}>,
     *     stickerChoices: list<string>,
     * }
     */
    public function forActivity(Activity $activity): array
    {
        $activity->loadMissing(['items.contentItem', 'items.pairedItem', 'directions']);

        return [
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'game_type' => $activity->game_type->value,
                'language' => $activity->language->value,
                'settings' => $activity->settings,
                'background_url' => $activity->backgroundUrl(),
            ],
            'cards' => $activity->items->map(fn (ActivityItem $item): array => [
                'word' => $item->contentItem->toPicture(),
                'pair' => $item->pairedItem?->toPicture(),
                'group_number' => $item->group_number,
                'category' => $item->contentItem->category,
            ])->values()->all(),
            'directions' => $activity->directions->map(fn (ActivityDirection $direction): array => [
                'id' => $direction->id,
                'text' => $direction->text,
                'item_ids' => $direction->item_ids,
            ])->values()->all(),
            'stickerChoices' => StickerSet::tryFrom((string) ($activity->settings['sticker_set'] ?? ''))?->stickers() ?? [],
        ];
    }
}
