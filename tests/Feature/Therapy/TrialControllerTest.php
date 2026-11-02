<?php

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\TrialResult;
use App\Models\Activity;
use App\Models\ActivityDirection;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Create a session in progress for a sound drill with one word.
 *
 * @return array{0: User, 1: PlaySession, 2: ContentItem}
 */
function sessionInProgress(): array
{
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();
    $word = ContentItem::factory()->starter()->create();
    $activity->items()->create(['content_item_id' => $word->id, 'position' => 0]);
    $session = PlaySession::factory()->for(Client::factory()->for($user))->for($activity)->create();

    return [$user, $session, $word];
}

test('answers are recorded in a batch', function () {
    [$user, $session, $word] = sessionInProgress();
    $uuids = [(string) Str::uuid(), (string) Str::uuid()];

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => $uuids[0], 'content_item_id' => $word->id, 'result' => 'correct', 'response_ms' => 1200],
            ['client_uuid' => $uuids[1], 'content_item_id' => $word->id, 'result' => 'prompted'],
        ]])
        ->assertOk()
        ->assertJson(['saved' => $uuids, 'recorded_trials' => 2]);

    expect($session->trials->pluck('result')->all())->toBe([TrialResult::Correct, TrialResult::Prompted]);
});

test('sending the same answer again updates it instead of adding a duplicate', function () {
    [$user, $session, $word] = sessionInProgress();
    $uuid = (string) Str::uuid();

    foreach (['incorrect', 'correct'] as $result) {
        $this->actingAs($user)
            ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
                ['client_uuid' => $uuid, 'content_item_id' => $word->id, 'result' => $result],
            ]])
            ->assertOk();
    }

    expect($session->trials()->sole()->result)->toBe(TrialResult::Correct);
});

test('a find it answer records the picture chosen instead', function () {
    [$user, $session, $word] = sessionInProgress();
    $distractor = ContentItem::factory()->starter()->create();
    $session->activity->items()->create(['content_item_id' => $distractor->id, 'position' => 1]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'incorrect', 'response_ms' => 2100, 'meta' => ['chosen_item_id' => $distractor->id]],
        ]])
        ->assertOk();

    expect($session->trials()->sole())
        ->content_item_id->toBe($word->id)
        ->response_ms->toBe(2100)
        ->meta->toBe(['chosen_item_id' => $distractor->id]);
});

test('the chosen picture must be in the activity', function () {
    [$user, $session, $word] = sessionInProgress();

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'incorrect', 'meta' => ['chosen_item_id' => ContentItem::factory()->starter()->create()->id]],
        ]])
        ->assertJsonValidationErrors('trials.0.meta.chosen_item_id');

    expect($session->trials()->count())->toBe(0);
});

test('a story answer is recorded for the whole story with its attempts', function () {
    [$user, $session] = sessionInProgress();
    $session->activity->update(['game_type' => GameType::StorySequence]);
    $session->activity->items()->update(['group_number' => 1]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => null, 'group_number' => 1, 'result' => 'prompted', 'meta' => ['attempts' => 2]],
        ]])
        ->assertOk();

    expect($session->trials()->sole())
        ->content_item_id->toBeNull()
        ->group_number->toBe(1)
        ->result->toBe(TrialResult::Prompted)
        ->meta->toBe(['attempts' => 2]);
});

test('a story answer must name a story in the activity', function () {
    [$user, $session] = sessionInProgress();
    $session->activity->items()->update(['group_number' => 1]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'group_number' => 2, 'result' => 'correct'],
        ]])
        ->assertJsonValidationErrors('trials.0.group_number');

    expect($session->trials()->count())->toBe(0);
});

test('a sort it answer records the group the picture was first sorted into', function () {
    [$user, $session, $word] = sessionInProgress();
    $session->activity->update(['game_type' => GameType::SortIt, 'settings' => [...$session->activity->settings, 'categories' => ['haiwan', 'makanan']]]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'prompted', 'meta' => ['chosen_category' => 'makanan']],
        ]])
        ->assertOk();

    expect($session->trials()->sole()->meta)->toBe(['chosen_category' => 'makanan']);
});

test('a sort it answer can only name one of the activity groups', function () {
    [$user, $session, $word] = sessionInProgress();
    $session->activity->update(['game_type' => GameType::SortIt, 'settings' => [...$session->activity->settings, 'categories' => ['haiwan', 'makanan']]]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'incorrect', 'meta' => ['chosen_category' => 'pakaian']],
        ]])
        ->assertJsonValidationErrors('trials.0.meta.chosen_category');

    expect($session->trials()->count())->toBe(0);
});

test('a follow directions answer is recorded for its direction with the pictures touched', function () {
    [$user, $session, $word] = sessionInProgress();
    $session->activity->update(['game_type' => GameType::FollowDirections]);
    $direction = $session->activity->directions()->create(['text' => 'Sentuh bola', 'item_ids' => [$word->id], 'position' => 0]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'activity_direction_id' => $direction->id, 'result' => 'correct', 'meta' => ['touched_item_ids' => [$word->id]]],
        ]])
        ->assertOk();

    expect($session->trials()->sole())
        ->content_item_id->toBeNull()
        ->activity_direction_id->toBe($direction->id)
        ->meta->toBe(['touched_item_ids' => [$word->id]]);
});

test('a follow directions answer must name a direction in the activity', function () {
    [$user, $session, $word] = sessionInProgress();
    $session->activity->update(['game_type' => GameType::FollowDirections]);
    $otherDirection = ActivityDirection::factory()->create(['item_ids' => [$word->id]]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'activity_direction_id' => $otherDirection->id, 'result' => 'correct'],
        ]])
        ->assertJsonValidationErrors('trials.0.activity_direction_id');

    expect($session->trials()->count())->toBe(0);
});

test('a balloon voice try is recorded with the time held and average level', function () {
    [$user, $session] = sessionInProgress();
    $session->activity->update(['game_type' => GameType::BalloonVoice]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'result' => 'correct', 'meta' => ['held_ms' => 3016, 'average_level' => 1.234]],
        ]])
        ->assertOk();

    expect($session->trials()->sole())
        ->content_item_id->toBeNull()
        ->meta->toBe(['held_ms' => 3016, 'average_level' => 1.23]);
});

test('a balloon voice try cannot name a word', function () {
    [$user, $session, $word] = sessionInProgress();
    $session->activity->update(['game_type' => GameType::BalloonVoice]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'correct'],
        ]])
        ->assertJsonValidationErrors('trials.0.content_item_id');

    expect($session->trials()->count())->toBe(0);
});

test('answers must be for a word in the activity', function () {
    [$user, $session] = sessionInProgress();

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => ContentItem::factory()->starter()->create()->id, 'result' => 'correct'],
        ]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('trials.0.content_item_id');
});

test('answers cannot be added to a finished session', function () {
    [$user, $session, $word] = sessionInProgress();
    $session->update(['ended_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'correct'],
        ]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['trials' => 'This session has already ended.']);
});

test('answers cannot be recorded on another clinician\'s session', function () {
    [, $session, $word] = sessionInProgress();

    $this->actingAs(User::factory()->create())
        ->postJson(route('play-sessions.trials.store', $session), ['trials' => [
            ['client_uuid' => (string) Str::uuid(), 'content_item_id' => $word->id, 'result' => 'correct'],
        ]])
        ->assertForbidden();

    expect($session->trials()->count())->toBe(0);
});
