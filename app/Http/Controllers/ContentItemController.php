<?php

namespace App\Http\Controllers;

use App\Enums\Therapy\Language;
use App\Enums\Therapy\WordPosition;
use App\Http\Requests\ContentItemRequest;
use App\Models\ActivityItem;
use App\Models\ContentItem;
use App\Models\Trial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ContentItemController extends Controller
{
    /**
     * Show the word library for one language.
     */
    public function index(Request $request): Response
    {
        $language = $request->enum('language', Language::class) ?? Language::Malay;

        $items = ContentItem::query()
            ->visibleTo($request->user())
            ->where('language', $language)
            ->orderBy('target_sound')
            ->orderBy('word')
            ->get();

        return Inertia::render('library/Index', [
            'language' => $language->value,
            'items' => $items->map(fn (ContentItem $item): array => $this->present($item)),
            'options' => [
                'languages' => Language::options(),
                'wordPositions' => WordPosition::options(),
            ],
        ]);
    }

    /**
     * Add a word to the clinician's library.
     */
    public function store(ContentItemRequest $request): RedirectResponse
    {
        $item = $request->user()->contentItems()->make($request->contentItemAttributes());

        if ($request->hasFile('image')) {
            $item->image_path = $this->storeImage($request->file('image'));
        }

        if ($request->hasFile('sound')) {
            $item->sound_path = $this->storeSound($request->file('sound'));
        }

        $item->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word added.')]);

        return to_route('content-items.index', ['language' => $item->language->value]);
    }

    /**
     * Update one of the clinician's own words.
     */
    public function update(ContentItemRequest $request, ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('update', $contentItem);

        $contentItem->fill($request->contentItemAttributes());

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $this->deleteImage($contentItem);
            $contentItem->image_path = $request->hasFile('image')
                ? $this->storeImage($request->file('image'))
                : null;
        }

        if ($request->hasFile('sound') || $request->boolean('remove_sound')) {
            $this->deleteSound($contentItem);
            $contentItem->sound_path = $request->hasFile('sound')
                ? $this->storeSound($request->file('sound'))
                : null;
        }

        $contentItem->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word updated.')]);

        return to_route('content-items.index', ['language' => $contentItem->language->value]);
    }

    /**
     * Delete one of the clinician's own words, unless it is in use.
     */
    public function destroy(ContentItem $contentItem): RedirectResponse
    {
        Gate::authorize('delete', $contentItem);

        $isInUse = ActivityItem::query()
            ->where('content_item_id', $contentItem->id)
            ->orWhere('paired_item_id', $contentItem->id)
            ->exists()
            || Trial::query()->where('content_item_id', $contentItem->id)->exists();

        if ($isInUse) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This word is used in an activity or has recorded answers, so it cannot be deleted.')]);

            return to_route('content-items.index', ['language' => $contentItem->language->value]);
        }

        $this->deleteImage($contentItem);
        $this->deleteSound($contentItem);
        $contentItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word deleted.')]);

        return to_route('content-items.index', ['language' => $contentItem->language->value]);
    }

    /**
     * Get the fields shown in the library for a word.
     *
     * @return array<string, mixed>
     */
    private function present(ContentItem $item): array
    {
        return [
            ...$item->toPicture(),
            'language' => $item->language->value,
            'target_sound' => $item->target_sound,
            'word_position' => $item->word_position?->value,
            'word_position_label' => $item->word_position?->label(),
            'category' => $item->category,
            'is_starter' => $item->isStarter(),
        ];
    }

    /**
     * Store an uploaded picture on the public disk and get its path.
     *
     * @throws RuntimeException When the file could not be written.
     */
    private function storeImage(UploadedFile $image): string
    {
        $path = $image->store('content-items', 'public');

        if ($path === false) {
            throw new RuntimeException('The picture could not be saved.');
        }

        return $path;
    }

    /**
     * Store an uploaded sound clip on the public disk and get its path.
     *
     * @throws RuntimeException When the file could not be written.
     */
    private function storeSound(UploadedFile $sound): string
    {
        $path = $sound->store('content-items/sounds', 'public');

        if ($path === false) {
            throw new RuntimeException('The sound could not be saved.');
        }

        return $path;
    }

    /**
     * Remove the word's uploaded sound clip from storage.
     */
    private function deleteSound(ContentItem $contentItem): void
    {
        if ($contentItem->sound_path !== null) {
            Storage::disk('public')->delete($contentItem->sound_path);
        }
    }

    /**
     * Remove the word's uploaded picture from storage.
     */
    private function deleteImage(ContentItem $contentItem): void
    {
        if ($contentItem->image_path !== null) {
            Storage::disk('public')->delete($contentItem->image_path);
        }
    }
}
