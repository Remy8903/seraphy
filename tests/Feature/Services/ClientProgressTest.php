<?php

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\WordPosition;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\Trial;
use App\Services\Therapy\ClientProgress;

test('accuracy is reported per finished session, oldest first', function () {
    $client = Client::factory()->create();
    $activity = Activity::factory()->for($client->user)->create(['title' => '/s/ drill']);
    $first = PlaySession::factory()->for($client)->for($activity)->finished()->create(['started_at' => now()->subWeek()]);
    Trial::factory()->for($first)->correct()->create();
    Trial::factory()->for($first)->prompted()->create();
    Trial::factory()->for($first)->incorrect()->count(2)->create();
    $second = PlaySession::factory()->for($client)->for($activity)->finished()->create(['started_at' => now()->subDay()]);
    Trial::factory()->for($second)->correct()->count(4)->create();
    $unfinished = PlaySession::factory()->for($client)->for($activity)->create();
    Trial::factory()->for($unfinished)->incorrect()->count(5)->create();

    $activities = (new ClientProgress)->summarize($client)['activities'];

    expect($activities)->toHaveCount(1);
    expect($activities[0]['title'])->toBe('/s/ drill');
    expect($activities[0]['sessions'])->toHaveCount(2);
    expect($activities[0]['sessions'][0])->toMatchArray([
        'id' => $first->id,
        'correct' => 1,
        'prompted' => 1,
        'incorrect' => 2,
        'total' => 4,
        'accuracy' => 25.0,
    ]);
    expect($activities[0]['sessions'][1]['accuracy'])->toBe(100.0);
});

test('accuracy is broken down by target sound and word position', function () {
    $client = Client::factory()->create();
    $session = PlaySession::factory()->for($client)->finished()->create();
    $initialS = ContentItem::factory()->starter()->create(['target_sound' => 's', 'word_position' => WordPosition::Initial]);
    $finalS = ContentItem::factory()->starter()->create(['target_sound' => 's', 'word_position' => WordPosition::Final]);
    Trial::factory()->for($session)->for($initialS)->correct()->count(3)->create();
    Trial::factory()->for($session)->for($initialS)->incorrect()->create();
    Trial::factory()->for($session)->for($finalS)->incorrect()->count(2)->create();

    $sounds = collect((new ClientProgress)->summarize($client)['sounds'])->keyBy('word_position');

    expect($sounds['initial'])->toMatchArray(['target_sound' => 's', 'correct' => 3, 'incorrect' => 1, 'total' => 4, 'accuracy' => 75.0]);
    expect($sounds['final'])->toMatchArray(['target_sound' => 's', 'correct' => 0, 'total' => 2, 'accuracy' => 0.0]);
});

test('a session summary lists the pictures chosen instead of each word', function () {
    $session = PlaySession::factory()->finished()->create();
    $kucing = ContentItem::factory()->starter()->create(['word' => 'kucing']);
    $kuda = ContentItem::factory()->starter()->create(['word' => 'kuda']);
    $kunci = ContentItem::factory()->starter()->create(['word' => 'kunci']);
    Trial::factory()->for($session)->for($kucing)->incorrect()->count(2)->create(['meta' => ['chosen_item_id' => $kuda->id]]);
    Trial::factory()->for($session)->for($kucing)->incorrect()->create(['meta' => ['chosen_item_id' => $kunci->id]]);
    Trial::factory()->for($session)->for($kucing)->correct()->create();

    $confusions = (new ClientProgress)->summarizeSession($session)['words'][0]['confusions'];

    expect($confusions)->toHaveCount(2);
    expect($confusions[0])->toMatchArray(['word' => 'kuda', 'count' => 2]);
    expect($confusions[1])->toMatchArray(['word' => 'kunci', 'count' => 1]);
});

test('a session summary lists each story with its cards in order and attempts', function () {
    $activity = Activity::factory()->create(['game_type' => GameType::StorySequence]);
    [$wake, $brush, $eat] = ContentItem::factory()->starter()->count(3)->create();
    foreach ([$eat, $wake, $brush] as $position => $card) {
        $activity->items()->create(['content_item_id' => $card->id, 'group_number' => 1, 'position' => [2, 0, 1][$position]]);
    }
    $session = PlaySession::factory()->for($activity)->finished()->create();
    Trial::factory()->for($session)->prompted()->create(['content_item_id' => null, 'group_number' => 1, 'meta' => ['attempts' => 3]]);

    $summary = (new ClientProgress)->summarizeSession($session);

    expect($summary['words'])->toBeEmpty();
    expect($summary['total'])->toBe(1);
    expect($summary['stories'][0])->toMatchArray(['group_number' => 1, 'result' => 'prompted', 'attempts' => 3]);
    expect(array_column($summary['stories'][0]['cards'], 'id'))->toBe([$wake->id, $brush->id, $eat->id]);
});

test('follow directions accuracy is broken down by number of steps', function () {
    $activity = Activity::factory()->create(['game_type' => GameType::FollowDirections]);
    [$ball, $cat, $cup] = ContentItem::factory()->starter()->count(3)->create();
    foreach ([$ball, $cat, $cup] as $position => $picture) {
        $activity->items()->create(['content_item_id' => $picture->id, 'position' => $position]);
    }
    $oneStep = $activity->directions()->create(['text' => 'Sentuh bola', 'item_ids' => [$ball->id], 'position' => 0]);
    $twoStep = $activity->directions()->create(['text' => 'Sentuh kucing, kemudian cawan', 'item_ids' => [$cat->id, $cup->id], 'position' => 1]);
    $session = PlaySession::factory()->for($activity)->finished()->create();
    Trial::factory()->for($session)->correct()->count(2)->create(['content_item_id' => null, 'activity_direction_id' => $oneStep->id]);
    Trial::factory()->for($session)->incorrect()->create(['content_item_id' => null, 'activity_direction_id' => $twoStep->id, 'meta' => ['touched_item_ids' => [$cup->id, $cat->id]]]);
    Trial::factory()->for($session)->prompted()->create(['content_item_id' => null, 'activity_direction_id' => $twoStep->id]);

    $summary = (new ClientProgress)->summarizeSession($session);

    expect($summary['steps'])->toBe([
        ['steps' => 1, 'correct' => 2, 'prompted' => 0, 'incorrect' => 0, 'total' => 2, 'accuracy' => 100.0],
        ['steps' => 2, 'correct' => 0, 'prompted' => 1, 'incorrect' => 1, 'total' => 2, 'accuracy' => 0.0],
    ]);
    expect($summary['directions'][2])->toMatchArray(['text' => 'Sentuh kucing, kemudian cawan', 'steps' => 2, 'result' => 'incorrect']);
    expect(array_column($summary['directions'][2]['touched'], 'id'))->toBe([$cup->id, $cat->id]);
    expect((new ClientProgress)->summarize($session->client)['steps'])->toBe($summary['steps']);
});

test('sort it accuracy is broken down by word group with the groups chosen instead', function () {
    $activity = Activity::factory()->create(['game_type' => GameType::SortIt, 'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'categories' => ['haiwan', 'makanan']]]);
    $cat = ContentItem::factory()->starter()->create(['category' => 'haiwan']);
    $cake = ContentItem::factory()->starter()->create(['category' => 'makanan']);
    $session = PlaySession::factory()->for($activity)->finished()->create();
    Trial::factory()->for($session)->for($cat)->correct()->create();
    Trial::factory()->for($session)->for($cat)->prompted()->create(['meta' => ['chosen_category' => 'makanan']]);
    Trial::factory()->for($session)->for($cake)->incorrect()->create(['meta' => ['chosen_category' => 'haiwan']]);

    $categories = (new ClientProgress)->summarizeSession($session)['categories'];

    expect($categories[0])->toMatchArray(['category' => 'haiwan', 'correct' => 1, 'prompted' => 1, 'total' => 2, 'accuracy' => 50.0, 'confusions' => [['category' => 'makanan', 'count' => 1]]]);
    expect($categories[1])->toMatchArray(['category' => 'makanan', 'incorrect' => 1, 'confusions' => [['category' => 'haiwan', 'count' => 1]]]);
    expect((new ClientProgress)->summarize($session->client)['categories'])->toBe([
        ['category' => 'haiwan', 'correct' => 1, 'prompted' => 1, 'incorrect' => 0, 'total' => 2, 'accuracy' => 50.0],
        ['category' => 'makanan', 'correct' => 0, 'prompted' => 0, 'incorrect' => 1, 'total' => 1, 'accuracy' => 0.0],
    ]);
});

test('a balloon voice session summary lists each try', function () {
    $session = PlaySession::factory()->for(Activity::factory()->create(['game_type' => GameType::BalloonVoice]))->finished()->create();
    Trial::factory()->for($session)->correct()->create(['content_item_id' => null, 'meta' => ['held_ms' => 3000, 'average_level' => 1.42]]);
    Trial::factory()->for($session)->incorrect()->create(['content_item_id' => null, 'meta' => ['held_ms' => 800]]);

    $summary = (new ClientProgress)->summarizeSession($session);

    expect($summary['tries'])->toBe([
        ['result' => 'correct', 'held_ms' => 3000, 'average_level' => 1.42],
        ['result' => 'incorrect', 'held_ms' => 800, 'average_level' => null],
    ]);
    expect($summary)->toMatchArray(['total' => 2, 'accuracy' => 50.0, 'words' => []]);
});

test('a session summary tallies answers per word', function () {
    $session = PlaySession::factory()->finished()->create();
    $word = ContentItem::factory()->starter()->create(['word' => 'sabun']);
    Trial::factory()->for($session)->for($word)->correct()->count(2)->create();
    Trial::factory()->for($session)->for($word)->prompted()->create();

    $summary = (new ClientProgress)->summarizeSession($session);

    expect($summary)->toMatchArray(['correct' => 2, 'prompted' => 1, 'incorrect' => 0, 'total' => 3, 'accuracy' => 66.7]);
    expect($summary['words'][0])->toMatchArray(['word' => 'sabun', 'correct' => 2, 'prompted' => 1, 'incorrect' => 0]);
});
