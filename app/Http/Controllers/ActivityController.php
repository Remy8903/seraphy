<?php

namespace App\Http\Controllers;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\Language;
use App\Enums\Therapy\StickerSet;
use App\Enums\Therapy\VoiceLevel;
use App\Enums\Therapy\WordPosition;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\ActivityDirection;
use App\Models\ActivityItem;
use App\Models\ContentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ActivityController extends Controller
{
    /**
     * Show the clinician's activities.
     */
    public function index(Request $request): Response
    {
        $activities = $request->user()
            ->activities()
            ->withCount(['items', 'clients', 'playSessions'])
            ->orderBy('title')
            ->get();

        return Inertia::render('activities/Index', [
            'activities' => $activities->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'title' => $activity->title,
                'game_type' => $activity->game_type->value,
                'game_type_label' => $activity->game_type->label(),
                'language' => $activity->language->label(),
                'items_count' => (int) $activity->getAttribute('items_count'),
                'clients_count' => (int) $activity->getAttribute('clients_count'),
                'play_sessions_count' => (int) $activity->getAttribute('play_sessions_count'),
            ]),
        ]);
    }

    /**
     * Show the activity builder for a new activity.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('activities/Create', [
            ...$this->builderProps($request),
        ]);
    }

    /**
     * Store a new activity with its words.
     */
    public function store(ActivityRequest $request): RedirectResponse
    {
        $backgroundPath = $request->hasFile('background')
            ? $this->storeBackground($request->file('background'))
            : null;

        DB::transaction(function () use ($request, $backgroundPath): void {
            $activity = $request->user()->activities()->make($request->activityAttributes());
            $activity->background_path = $backgroundPath;
            $activity->save();

            $activity->items()->createMany($request->itemAttributes());
            $this->syncDirections($activity, $request->directionAttributes());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity saved.')]);

        return to_route('activities.index');
    }

    /**
     * Show the activity builder for an existing activity.
     */
    public function edit(Request $request, Activity $activity): Response
    {
        Gate::authorize('update', $activity);

        $activity->load(['items', 'directions']);

        return Inertia::render('activities/Edit', [
            ...$this->builderProps($request),
            'activity' => [
                'id' => $activity->id,
                'game_type' => $activity->game_type->value,
                'title' => $activity->title,
                'language' => $activity->language->value,
                'settings' => $activity->settings,
                'background_url' => $activity->backgroundUrl(),
                'items' => $activity->items->map(fn (ActivityItem $item): array => [
                    'content_item_id' => $item->content_item_id,
                    'paired_item_id' => $item->paired_item_id,
                    'group_number' => $item->group_number,
                ]),
                'directions' => $activity->directions->map(fn (ActivityDirection $direction): array => [
                    'id' => $direction->id,
                    'text' => $direction->text,
                    'item_ids' => $direction->item_ids,
                ]),
            ],
        ]);
    }

    /**
     * Update an activity and replace its words.
     */
    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        Gate::authorize('update', $activity);

        $previousBackgroundPath = $activity->background_path;
        $replacesBackground = $request->hasFile('background') || $request->boolean('remove_background');

        if ($replacesBackground) {
            $activity->background_path = $request->hasFile('background')
                ? $this->storeBackground($request->file('background'))
                : null;
        }

        DB::transaction(function () use ($request, $activity): void {
            $activity->fill($request->activityAttributes())->save();

            $activity->items()->delete();
            $activity->items()->createMany($request->itemAttributes());
            $this->syncDirections($activity, $request->directionAttributes());
        });

        if ($replacesBackground) {
            $this->deleteBackground($previousBackgroundPath);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity updated.')]);

        return to_route('activities.index');
    }

    /**
     * Delete an activity with every session played with it.
     */
    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('delete', $activity);

        $activity->delete();
        $this->deleteBackground($activity->background_path);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity deleted.')]);

        return to_route('activities.index');
    }

    /**
     * Replace an activity's directions, updating existing ones in place so answers keep their direction.
     *
     * @param  list<array{id: int|null, text: string, item_ids: list<int>, position: int}>  $directions
     */
    private function syncDirections(Activity $activity, array $directions): void
    {
        $existing = $activity->directions()->get()->keyBy('id');

        $activity->directions()->whereKeyNot(array_filter(array_column($directions, 'id')))->delete();

        foreach ($directions as $direction) {
            $attributes = Arr::except($direction, 'id');

            if ($direction['id'] !== null && $existing->has($direction['id'])) {
                $existing->get($direction['id'])->update($attributes);
            } else {
                $activity->directions()->create($attributes);
            }
        }
    }

    /**
     * Store an uploaded background picture on the public disk and get its path.
     *
     * @throws RuntimeException When the file could not be written.
     */
    private function storeBackground(UploadedFile $background): string
    {
        $path = $background->store('activity-backgrounds', 'public');

        if ($path === false) {
            throw new RuntimeException('The background picture could not be saved.');
        }

        return $path;
    }

    /**
     * Remove a background picture that is no longer used from storage.
     */
    private function deleteBackground(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Get the choices and word library shown in the activity builder.
     *
     * @return array<string, mixed>
     */
    private function builderProps(Request $request): array
    {
        $library = ContentItem::query()
            ->visibleTo($request->user())
            ->orderBy('target_sound')
            ->orderBy('word')
            ->get();

        return [
            'options' => [
                'gameTypes' => array_map(fn (GameType $gameType): array => [
                    'value' => $gameType->value,
                    'label' => $gameType->label(),
                    'description' => $gameType->description(),
                    'uses_pairs' => $gameType->usesPairs(),
                    'uses_stories' => $gameType->usesStories(),
                    'uses_words' => $gameType->usesWords(),
                    'uses_categories' => $gameType->usesCategories(),
                    'uses_directions' => $gameType->usesDirections(),
                    'uses_sounds' => $gameType->usesSounds(),
                ], GameType::cases()),
                'languages' => Language::options(),
                'wordPositions' => WordPosition::options(),
                'stickerSets' => array_map(fn (StickerSet $set): array => [
                    'value' => $set->value,
                    'label' => $set->label(),
                    'stickers' => $set->stickers(),
                ], StickerSet::cases()),
                'voiceLevels' => VoiceLevel::options(),
            ],
            'library' => $library->map(fn (ContentItem $item): array => [
                ...$item->toPicture(),
                'language' => $item->language->value,
                'target_sound' => $item->target_sound,
                'word_position' => $item->word_position?->value,
                'category' => $item->category,
                'pair_key' => $item->pair_key,
                'story_key' => $item->story_key,
                'story_position' => $item->story_position,
            ]),
        ];
    }
}
