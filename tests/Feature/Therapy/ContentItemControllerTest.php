<?php

use App\Models\ActivityItem;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('the library shows starter words and the clinician\'s own words in the chosen language', function () {
    $user = User::factory()->create();
    $own = ContentItem::factory()->for($user)->english()->create(['word' => 'zebra']);
    ContentItem::factory()->english()->create(['word' => 'someone else']);

    $this->actingAs($user)
        ->get(route('content-items.index', ['language' => 'en']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('library/Index')
            ->where('language', 'en')
            ->where('items', fn ($items) => collect($items)->contains('id', $own->id)
                && collect($items)->contains(fn ($item) => $item['is_starter'])
                && ! collect($items)->contains('word', 'someone else')
                && collect($items)->every(fn ($item) => $item['language'] === 'en')),
        );
});

test('a word can be added with an uploaded picture', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('content-items.store'), [
            'language' => 'ms',
            'word' => 'kerusi',
            'image' => UploadedFile::fake()->image('kerusi.png', 300, 300),
            'target_sound' => 'k',
            'word_position' => 'initial',
            'category' => 'rumah',
        ])
        ->assertSessionHasNoErrors();

    $item = $user->contentItems()->sole();
    expect($item->word)->toBe('kerusi');
    Storage::disk('public')->assertExists($item->image_path);
});

test('a word needs an emoji or a picture', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('content-items.store'), ['language' => 'ms', 'word' => 'kerusi'])
        ->assertSessionHasErrors(['emoji' => 'Add an emoji or upload a picture.']);
});

test('removing a picture deletes the file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('old.png')->store('content-items', 'public');
    $item = ContentItem::factory()->for($user)->create(['image_path' => $path]);

    $this->actingAs($user)
        ->put(route('content-items.update', $item), [
            'language' => 'ms',
            'word' => $item->word,
            'emoji' => '🪑',
            'remove_image' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($item->refresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('a word used in an activity cannot be deleted', function () {
    $user = User::factory()->create();
    $item = ContentItem::factory()->for($user)->create();
    ActivityItem::factory()->create(['content_item_id' => $item->id]);

    $this->actingAs($user)->delete(route('content-items.destroy', $item));

    expect($item->fresh())->not->toBeNull();
});

test('starter words and other clinicians\' words cannot be changed', function (Closure $makeItem) {
    $item = $makeItem();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('content-items.update', $item), ['language' => 'ms', 'word' => 'changed', 'emoji' => '🐱'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('content-items.destroy', $item))
        ->assertForbidden();
})->with([
    'starter word' => fn () => ContentItem::factory()->starter()->create(),
    'other clinician\'s word' => fn () => ContentItem::factory()->create(),
]);

test('the starter library is loaded by the migrations', function () {
    expect(ContentItem::query()->whereNull('user_id')->where('language', 'ms')->count())->toBeGreaterThan(50);
    expect(ContentItem::query()->whereNull('user_id')->where('language', 'en')->count())->toBeGreaterThan(50);
});
