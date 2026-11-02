<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\PlaySession;
use App\Models\Trial;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create a session for an activity that gives an animal sticker every 5 answers.
 *
 * @return array{0: User, 1: PlaySession}
 */
function sessionWithStickers(): array
{
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create([
        'settings' => ['trial_count' => 10, 'shuffle' => true, 'show_word_text' => false, 'reward_every' => 5, 'sticker_set' => 'animals'],
    ]);
    $session = PlaySession::factory()->for(Client::factory()->for($user))->for($activity)->create();

    return [$user, $session];
}

test('stickers are saved on the session and shown on the summary', function () {
    [$user, $session] = sessionWithStickers();

    $this->actingAs($user)
        ->putJson(route('play-sessions.stickers.update', $session), ['stickers' => ['🐶', '🦁']])
        ->assertOk()
        ->assertJson(['stickers' => ['🐶', '🦁']]);

    Trial::factory()->for($session)->correct()->create();
    $session->update(['ended_at' => now()]);

    $this->actingAs($user)
        ->get(route('play-sessions.summary', $session))
        ->assertInertia(fn (Assert $page) => $page->where('session.stickers', ['🐶', '🦁']));
});

test('the play screen offers the activity\'s sticker set and the stickers already earned', function () {
    [$user, $session] = sessionWithStickers();
    $session->update(['stickers' => ['🐼']]);

    $this->actingAs($user)
        ->get(route('play-sessions.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.stickers', ['🐼'])
            ->has('stickerChoices', 12)
            ->where('stickerChoices.0', '🐶'),
        );
});

test('stickers must come from the activity\'s sticker set', function () {
    [$user, $session] = sessionWithStickers();

    $this->actingAs($user)
        ->putJson(route('play-sessions.stickers.update', $session), ['stickers' => ['🚀']])
        ->assertJsonValidationErrors(['stickers.0' => 'Choose stickers from the activity\'s sticker set.']);

    expect($session->refresh()->stickers)->toBeNull();
});

test('stickers cannot be saved on another clinician\'s session', function () {
    [, $session] = sessionWithStickers();

    $this->actingAs(User::factory()->create())
        ->putJson(route('play-sessions.stickers.update', $session), ['stickers' => ['🐶']])
        ->assertForbidden();

    expect($session->refresh()->stickers)->toBeNull();
});
