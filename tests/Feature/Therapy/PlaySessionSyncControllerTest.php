<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Create a clinician with a client and a one-word sound drill they could have played offline.
 *
 * @return array{0: User, 1: Client, 2: Activity, 3: ContentItem}
 */
function offlineSetup(): array
{
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $activity = Activity::factory()->for($user)->create([
        'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'reward_every' => 5, 'sticker_set' => 'animals'],
    ]);
    $word = ContentItem::factory()->starter()->create();
    $activity->items()->create(['content_item_id' => $word->id, 'position' => 0]);
    $client->activities()->attach($activity);

    return [$user, $client, $activity, $word];
}

/**
 * Build one offline session as the device sends it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function offlineSession(Client $client, Activity $activity, ContentItem $word, array $overrides = []): array
{
    return [
        'client_uuid' => (string) Str::uuid(),
        'client_id' => $client->id,
        'activity_id' => $activity->id,
        'started_at' => '2026-11-02T01:00:00.000Z',
        'ended_at' => '2026-11-02T01:20:00.000Z',
        'stickers' => ['🐶'],
        'trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'correct', 'response_ms' => 1500],
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'incorrect', 'response_ms' => null],
        ],
        ...$overrides,
    ];
}

test('offline sessions are uploaded with their answers and stickers', function () {
    [$user, $client, $activity, $word] = offlineSetup();
    $session = offlineSession($client, $activity, $word);

    $this->actingAs($user)
        ->postJson(route('play-sessions.sync'), ['sessions' => [$session]])
        ->assertOk()
        ->assertJson(['synced' => [$session['client_uuid']]]);

    $synced = $client->playSessions()->sole();
    expect($synced)
        ->client_uuid->toBe($session['client_uuid'])
        ->activity_id->toBe($activity->id)
        ->stickers->toBe(['🐶'])
        ->and($synced->started_at->toIso8601String())->toBe('2026-11-02T01:00:00+00:00')
        ->and($synced->isFinished())->toBeTrue()
        ->and($synced->trials->pluck('result')->map->value->all())->toBe(['correct', 'incorrect']);
});

test('sending the same offline batch again adds nothing', function () {
    [$user, $client, $activity, $word] = offlineSetup();
    $batch = ['sessions' => [offlineSession($client, $activity, $word)]];

    $this->actingAs($user)->postJson(route('play-sessions.sync'), $batch)->assertOk();
    $this->actingAs($user)->postJson(route('play-sessions.sync'), $batch)->assertOk();

    expect(PlaySession::count())->toBe(1);
    expect($client->playSessions()->sole()->trials()->count())->toBe(2);
});

test('offline sessions for another clinician\'s client or activity are rejected', function (Closure $makeSession, string $errorKey) {
    [$user, $client, $activity, $word] = offlineSetup();

    $this->actingAs($user)
        ->postJson(route('play-sessions.sync'), ['sessions' => [$makeSession($client, $activity, $word)]])
        ->assertJsonValidationErrors($errorKey);

    expect(PlaySession::count())->toBe(0);
})->with([
    'other clinician\'s client' => [
        fn (Client $client, Activity $activity, ContentItem $word) => offlineSession($client, $activity, $word, ['client_id' => Client::factory()->create()->id]),
        'sessions.0.client_id',
    ],
    'other clinician\'s activity' => [
        fn (Client $client, Activity $activity, ContentItem $word) => offlineSession($client, $activity, $word, ['activity_id' => Activity::factory()->create()->id]),
        'sessions.0.activity_id',
    ],
]);

test('offline answers are checked against the session\'s activity', function () {
    [$user, $client, $activity, $word] = offlineSetup();
    $session = offlineSession($client, $activity, $word, [
        'stickers' => ['🚀'],
        'trials' => [['client_uuid' => (string) Str::uuid(), 'content_item_id' => ContentItem::factory()->starter()->create()->id, 'result' => 'correct']],
    ]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.sync'), ['sessions' => [$session]])
        ->assertJsonValidationErrors(['sessions.0.trials.0.content_item_id', 'sessions.0.stickers']);

    expect(PlaySession::count())->toBe(0);
});

test('a session already saved for another client cannot be overwritten', function () {
    [$user, $client, $activity, $word] = offlineSetup();
    $existing = PlaySession::factory()->finished()->create(['client_uuid' => (string) Str::uuid()]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.sync'), ['sessions' => [offlineSession($client, $activity, $word, ['client_uuid' => $existing->client_uuid])]])
        ->assertJsonValidationErrors('sessions.0.client_uuid');

    expect($existing->trials()->count())->toBe(0);
});

test('syncing offline sessions updates the client\'s goals', function () {
    [$user, $client, $activity, $word] = offlineSetup();
    $goal = $client->goals()->create(['description' => 'Says /s/ words', 'target_accuracy' => 50, 'consecutive_sessions' => 1, 'min_trials_per_session' => 2]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.sync'), ['sessions' => [offlineSession($client, $activity, $word)]])
        ->assertOk();

    expect($goal->refresh()->achieved_at)->not->toBeNull();
});
