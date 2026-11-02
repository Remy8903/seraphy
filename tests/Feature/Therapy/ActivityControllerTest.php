<?php

use App\Enums\Therapy\GameType;
use App\Models\Activity;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  list<array<string, int>>  $items
 * @return array<string, mixed>
 */
function validActivityPayload(array $items, array $overrides = []): array
{
    return [
        'game_type' => 'sound_drill',
        'title' => '/s/ initial',
        'language' => 'ms',
        'settings' => ['trial_count' => 20, 'shuffle' => true, 'show_word_text' => false],
        'items' => $items,
        ...$overrides,
    ];
}

test('the builder offers starter words and the clinician\'s own words', function () {
    $user = User::factory()->create();
    $own = ContentItem::factory()->for($user)->create();
    $other = ContentItem::factory()->create();

    $this->actingAs($user)
        ->get(route('activities.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('activities/Create')
            ->has('options.gameTypes', count(GameType::cases()))
            ->where('library', fn ($library) => collect($library)->contains('id', $own->id)
                && ! collect($library)->contains('id', $other->id)),
        );
});

test('a sound drill activity is stored with its words in order', function () {
    $user = User::factory()->create();
    [$first, $second] = ContentItem::factory()->starter()->count(2)->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload([
            ['content_item_id' => $second->id],
            ['content_item_id' => $first->id],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('activities.index'));

    $activity = $user->activities()->sole();
    expect($activity->settings)->toBe(['trial_count' => 20, 'shuffle' => true, 'show_word_text' => false, 'reward_every' => 0]);
    expect($activity->items->pluck('content_item_id')->all())->toBe([$second->id, $first->id]);
});

test('a minimal pairs activity needs a contrasting word for each pair', function () {
    $user = User::factory()->create();
    [$kopi, $topi] = ContentItem::factory()->starter()->count(2)->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload([['content_item_id' => $kopi->id]], ['game_type' => 'minimal_pairs']))
        ->assertSessionHasErrors('items.0.paired_item_id');

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload(
            [['content_item_id' => $kopi->id, 'paired_item_id' => $topi->id]],
            ['game_type' => 'minimal_pairs'],
        ))
        ->assertSessionHasNoErrors();

    expect($user->activities()->sole()->items()->sole()->paired_item_id)->toBe($topi->id);
});

test('words must match the activity language and be visible to the clinician', function (Closure $makeItem) {
    $this->actingAs(User::factory()->create())
        ->post(route('activities.store'), validActivityPayload([['content_item_id' => $makeItem()->id]]))
        ->assertSessionHasErrors('items.0.content_item_id');
})->with([
    'other language' => fn () => ContentItem::factory()->starter()->english()->create(),
    'other clinician\'s word' => fn () => ContentItem::factory()->create(),
]);

test('updating an activity replaces its words', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    $activity->items()->create(['content_item_id' => ContentItem::factory()->starter()->create()->id, 'position' => 0]);
    $replacement = ContentItem::factory()->starter()->create();

    $this->actingAs($user)
        ->put(route('activities.update', $activity), validActivityPayload([['content_item_id' => $replacement->id]]))
        ->assertSessionHasNoErrors();

    expect($activity->items()->pluck('content_item_id')->all())->toBe([$replacement->id]);
});

test('a find it activity stores its field size and speech setting', function () {
    $user = User::factory()->create();
    $words = ContentItem::factory()->starter()->count(3)->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload(
            $words->map(fn (ContentItem $word): array => ['content_item_id' => $word->id])->all(),
            [
                'game_type' => 'find_it',
                'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'field_size' => 5, 'speak_word' => true],
            ],
        ))
        ->assertSessionHasNoErrors();

    expect($user->activities()->sole()->settings)->toMatchArray(['field_size' => 5, 'speak_word' => true]);
});

test('a find it activity needs at least 3 words and a field of 3 to 6 pictures', function () {
    $user = User::factory()->create();
    $words = ContentItem::factory()->starter()->count(2)->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload(
            $words->map(fn (ContentItem $word): array => ['content_item_id' => $word->id])->all(),
            [
                'game_type' => 'find_it',
                'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'field_size' => 7, 'speak_word' => false],
            ],
        ))
        ->assertSessionHasErrors([
            'items' => 'Add at least 3 words so the child has pictures to choose from.',
            'settings.field_size',
        ]);

    expect(Activity::count())->toBe(0);
});

test('a story sequence activity stores each story\'s cards in order', function () {
    $user = User::factory()->create();
    $cards = ContentItem::factory()->starter()->count(4)->create();
    [$wake, $brush, $eat, $school] = $cards;

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload([
            ['content_item_id' => $wake->id, 'group_number' => 1],
            ['content_item_id' => $brush->id, 'group_number' => 1],
            ['content_item_id' => $eat->id, 'group_number' => 1],
            ['content_item_id' => $school->id, 'group_number' => 2],
            ['content_item_id' => $eat->id, 'group_number' => 2],
            ['content_item_id' => $wake->id, 'group_number' => 2],
        ], ['game_type' => 'story_sequence']))
        ->assertSessionHasNoErrors();

    $items = $user->activities()->sole()->items;
    expect($items->where('group_number', 1)->pluck('content_item_id')->values()->all())->toBe([$wake->id, $brush->id, $eat->id]);
    expect($items->where('group_number', 2)->pluck('content_item_id')->values()->all())->toBe([$school->id, $eat->id, $wake->id]);
});

test('each story needs 3 to 5 different cards', function (array $storyCardIndexes, string $message) {
    $user = User::factory()->create();
    $cards = ContentItem::factory()->starter()->count(6)->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload(
            array_map(fn (int $index): array => ['content_item_id' => $cards[$index]->id, 'group_number' => 1], $storyCardIndexes),
            ['game_type' => 'story_sequence'],
        ))
        ->assertSessionHasErrors(['items' => $message]);

    expect(Activity::count())->toBe(0);
})->with([
    'too short' => [[0, 1], 'Each story needs 3 to 5 cards.'],
    'too long' => [[0, 1, 2, 3, 4, 5], 'Each story needs 3 to 5 cards.'],
    'repeated card' => [[0, 1, 1], 'A card can only appear once in each story.'],
]);

test('sticker rewards are stored with their sticker set', function () {
    $user = User::factory()->create();
    $word = ContentItem::factory()->starter()->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload([['content_item_id' => $word->id]], [
            'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'reward_every' => 5, 'sticker_set' => 'space'],
        ]))
        ->assertSessionHasNoErrors();

    expect($user->activities()->sole()->settings)->toMatchArray(['reward_every' => 5, 'sticker_set' => 'space']);
});

test('turning sticker rewards on needs a sticker set', function () {
    $word = ContentItem::factory()->starter()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('activities.store'), validActivityPayload([['content_item_id' => $word->id]], [
            'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'reward_every' => 5],
        ]))
        ->assertSessionHasErrors(['settings.sticker_set' => 'Choose a sticker set, or turn stickers off.']);

    expect(Activity::count())->toBe(0);
});

test('a sort it activity stores the groups to sort into', function () {
    $user = User::factory()->create();
    $cat = ContentItem::factory()->starter()->create(['category' => 'haiwan']);
    $cake = ContentItem::factory()->starter()->create(['category' => 'makanan']);

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload(
            [['content_item_id' => $cat->id], ['content_item_id' => $cake->id]],
            [
                'game_type' => 'sort_it',
                'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'categories' => ['haiwan', 'makanan']],
            ],
        ))
        ->assertSessionHasNoErrors();

    expect($user->activities()->sole()->settings['categories'])->toBe(['haiwan', 'makanan']);
});

test('sort it pictures must fit the chosen groups', function (array $pictureCategories, string $message) {
    $items = array_map(
        fn (string $category): array => ['content_item_id' => ContentItem::factory()->starter()->create(['category' => $category])->id],
        $pictureCategories,
    );

    $this->actingAs(User::factory()->create())
        ->post(route('activities.store'), validActivityPayload($items, [
            'game_type' => 'sort_it',
            'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'categories' => ['haiwan', 'makanan']],
        ]))
        ->assertSessionHasErrors(['items' => $message]);

    expect(Activity::count())->toBe(0);
})->with([
    'picture outside the groups' => [['haiwan', 'makanan', 'pakaian'], 'Every picture must belong to one of the groups.'],
    'group without a picture' => [['haiwan', 'haiwan'], 'Add at least one picture to each group.'],
]);

/**
 * Build a follow directions payload with a board of the given pictures.
 *
 * @param  list<ContentItem>  $board
 * @param  list<array<string, mixed>>  $directions
 * @return array<string, mixed>
 */
function followDirectionsPayload(array $board, array $directions): array
{
    return validActivityPayload(
        array_map(fn (ContentItem $picture): array => ['content_item_id' => $picture->id], $board),
        [
            'game_type' => 'follow_directions',
            'settings' => ['trial_count' => 10, 'shuffle' => false, 'show_word_text' => false, 'speak_word' => false],
            'directions' => $directions,
        ],
    );
}

test('a follow directions activity stores its board and directions in order', function () {
    $user = User::factory()->create();
    [$ball, $cat, $cup, $car] = ContentItem::factory()->starter()->count(4)->create();

    $this->actingAs($user)
        ->post(route('activities.store'), followDirectionsPayload([$ball, $cat, $cup, $car], [
            ['text' => 'Sentuh bola', 'item_ids' => [$ball->id]],
            ['text' => 'Sentuh kereta, kemudian kucing', 'item_ids' => [$car->id, $cat->id]],
        ]))
        ->assertSessionHasNoErrors();

    $activity = $user->activities()->sole();
    expect($activity->items)->toHaveCount(4);
    expect($activity->directions->map->only(['text', 'item_ids', 'position'])->all())->toBe([
        ['text' => 'Sentuh bola', 'item_ids' => [$ball->id], 'position' => 0],
        ['text' => 'Sentuh kereta, kemudian kucing', 'item_ids' => [$car->id, $cat->id], 'position' => 1],
    ]);
});

test('follow directions need a board of 4 to 8 pictures and directions that use it', function (Closure $payload, string $errorKey) {
    $this->actingAs(User::factory()->create())
        ->post(route('activities.store'), $payload())
        ->assertSessionHasErrors($errorKey);

    expect(Activity::count())->toBe(0);
})->with([
    'board too small' => [function () {
        $board = ContentItem::factory()->starter()->count(3)->create();

        return followDirectionsPayload($board->all(), [['text' => 'Sentuh', 'item_ids' => [$board[0]->id]]]);
    }, 'items'],
    'picture not on the board' => [fn () => followDirectionsPayload(
        ContentItem::factory()->starter()->count(4)->create()->all(),
        [['text' => 'Sentuh', 'item_ids' => [ContentItem::factory()->starter()->create()->id]]],
    ), 'directions.0.item_ids.0'],
    'same picture twice' => [function () {
        $board = ContentItem::factory()->starter()->count(4)->create();

        return followDirectionsPayload($board->all(), [['text' => 'Sentuh', 'item_ids' => [$board[0]->id, $board[0]->id]]]);
    }, 'directions.0.item_ids'],
    'more than 4 steps' => [function () {
        $board = ContentItem::factory()->starter()->count(5)->create();

        return followDirectionsPayload($board->all(), [['text' => 'Sentuh', 'item_ids' => $board->modelKeys()]]);
    }, 'directions.0.item_ids'],
    'no directions' => [fn () => followDirectionsPayload(ContentItem::factory()->starter()->count(4)->create()->all(), []), 'directions'],
]);

test('updating a follow directions activity keeps existing directions in place', function () {
    $user = User::factory()->create();
    $board = ContentItem::factory()->starter()->count(4)->create();
    $activity = Activity::factory()->for($user)->create(['game_type' => GameType::FollowDirections]);
    $kept = $activity->directions()->create(['text' => 'Sentuh bola', 'item_ids' => [$board[0]->id], 'position' => 0]);
    $removed = $activity->directions()->create(['text' => 'Sentuh kucing', 'item_ids' => [$board[1]->id], 'position' => 1]);

    $this->actingAs($user)
        ->put(route('activities.update', $activity), followDirectionsPayload($board->all(), [
            ['id' => $kept->id, 'text' => 'Sentuh bola merah', 'item_ids' => [$board[0]->id, $board[2]->id]],
            ['id' => null, 'text' => 'Sentuh cawan', 'item_ids' => [$board[3]->id]],
        ]))
        ->assertSessionHasNoErrors();

    expect($kept->refresh())->text->toBe('Sentuh bola merah')->item_ids->toBe([$board[0]->id, $board[2]->id]);
    expect($removed->fresh())->toBeNull();
    expect($activity->directions()->count())->toBe(2);
});

test('a balloon voice activity needs no words and stores its voice target', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload([], [
            'game_type' => 'balloon_voice',
            'settings' => ['trial_count' => 5, 'shuffle' => false, 'show_word_text' => false, 'target_level' => 'loud', 'hold_seconds' => 3],
        ]))
        ->assertSessionHasNoErrors();

    $activity = $user->activities()->sole();
    expect($activity->settings)->toMatchArray(['target_level' => 'loud', 'hold_seconds' => 3]);
    expect($activity->items)->toBeEmpty();
});

test('a balloon voice activity needs a voice target and hold time', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('activities.store'), validActivityPayload([], [
            'game_type' => 'balloon_voice',
            'settings' => ['trial_count' => 5, 'shuffle' => false, 'show_word_text' => false, 'target_level' => 'shout', 'hold_seconds' => 30],
        ]))
        ->assertSessionHasErrors(['settings.target_level', 'settings.hold_seconds']);

    expect(Activity::count())->toBe(0);
});

test('an activity can have an uploaded background picture', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $word = ContentItem::factory()->starter()->create();

    $this->actingAs($user)
        ->post(route('activities.store'), validActivityPayload([['content_item_id' => $word->id]], [
            'background' => UploadedFile::fake()->image('beach.jpg', 1600, 900),
        ]))
        ->assertSessionHasNoErrors();

    $activity = $user->activities()->sole();
    Storage::disk('public')->assertExists($activity->background_path);

    $this->get(route('activities.edit', $activity))
        ->assertInertia(fn (Assert $page) => $page->where('activity.background_url', $activity->backgroundUrl()));
});

test('replacing or removing a background picture deletes the old one', function (Closure $changes, bool $hasBackground) {
    Storage::fake('public');
    $user = User::factory()->create();
    $oldPath = UploadedFile::fake()->image('old.png')->store('activity-backgrounds', 'public');
    $activity = Activity::factory()->for($user)->create(['background_path' => $oldPath]);
    $word = ContentItem::factory()->starter()->create();

    $this->actingAs($user)
        ->put(route('activities.update', $activity), validActivityPayload([['content_item_id' => $word->id]], $changes()))
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($oldPath);
    expect($activity->refresh()->background_path !== null)->toBe($hasBackground);
})->with([
    'replaced' => [fn () => ['background' => UploadedFile::fake()->image('new.png')], true],
    'removed' => [fn () => ['remove_background' => true], false],
]);

test('saving an activity without a new picture keeps its background', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('kept.png')->store('activity-backgrounds', 'public');
    $activity = Activity::factory()->for($user)->create(['background_path' => $path]);
    $word = ContentItem::factory()->starter()->create();

    $this->actingAs($user)
        ->put(route('activities.update', $activity), validActivityPayload([['content_item_id' => $word->id]]))
        ->assertSessionHasNoErrors();

    expect($activity->refresh()->background_path)->toBe($path);
    Storage::disk('public')->assertExists($path);
});

test('the background must be a picture of 5 MB or smaller', function (Closure $file) {
    Storage::fake('public');
    $word = ContentItem::factory()->starter()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('activities.store'), validActivityPayload([['content_item_id' => $word->id]], ['background' => $file()]))
        ->assertSessionHasErrors('background');

    expect(Activity::count())->toBe(0);
})->with([
    'not a picture' => [fn () => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
    'too large' => [fn () => UploadedFile::fake()->image('huge.jpg')->size(6000)],
]);

test('deleting an activity deletes its background picture', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('old.png')->store('activity-backgrounds', 'public');
    $activity = Activity::factory()->for($user)->create(['background_path' => $path]);

    $this->actingAs($user)
        ->delete(route('activities.destroy', $activity))
        ->assertRedirect(route('activities.index'));

    Storage::disk('public')->assertMissing($path);
});

test('clinicians cannot access another clinician\'s activity', function (string $method, string $routeName) {
    $activity = Activity::factory()->create();
    $word = ContentItem::factory()->starter()->create();

    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName, $activity), validActivityPayload([['content_item_id' => $word->id]]))
        ->assertForbidden();

    expect($activity->refresh()->exists)->toBeTrue();
})->with([
    'edit' => ['GET', 'activities.edit'],
    'update' => ['PUT', 'activities.update'],
    'destroy' => ['DELETE', 'activities.destroy'],
]);
