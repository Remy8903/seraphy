<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\Trial;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a session can be started for an assigned activity', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $activity = Activity::factory()->for($user)->create();
    $client->activities()->attach($activity);

    $response = $this->actingAs($user)->post(route('play-sessions.store'), [
        'client_id' => $client->id,
        'activity_id' => $activity->id,
    ]);

    $session = $client->playSessions()->sole();
    $response->assertRedirect(route('play-sessions.show', $session));
    expect($session->activity_id)->toBe($activity->id);
});

test('a session cannot be started for an unassigned activity or another clinician\'s client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $activity = Activity::factory()->for($user)->create();
    $otherClient = Client::factory()->create();
    $otherClient->activities()->attach($activity);

    $this->actingAs($user)
        ->post(route('play-sessions.store'), ['client_id' => $client->id, 'activity_id' => $activity->id])
        ->assertSessionHasErrors('activity_id');

    $this->actingAs($user)
        ->post(route('play-sessions.store'), ['client_id' => $otherClient->id, 'activity_id' => $activity->id])
        ->assertSessionHasErrors('client_id');

    expect(PlaySession::count())->toBe(0);
});

test('the game screen gets the activity cards but not the client reference', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['reference_code' => 'C-77']);
    $activity = Activity::factory()->for($user)->minimalPairs()->create(['background_path' => 'activity-backgrounds/beach.jpg']);
    [$kopi, $topi] = ContentItem::factory()->starter()->count(2)->create();
    $activity->items()->create(['content_item_id' => $kopi->id, 'paired_item_id' => $topi->id, 'position' => 0]);
    $session = PlaySession::factory()->for($client)->for($activity)->create();

    $this->actingAs($user)
        ->get(route('play-sessions.show', $session))
        ->assertDontSee('C-77')
        ->assertInertia(fn (Assert $page) => $page
            ->component('play/Show')
            ->where('activity.game_type', 'minimal_pairs')
            ->where('activity.background_url', $activity->backgroundUrl())
            ->where('cards.0.word.id', $kopi->id)
            ->where('cards.0.pair.id', $topi->id)
            ->missing('client'),
        );
});

test('finishing a session with answers ends it and shows the summary', function () {
    $user = User::factory()->create();
    $session = PlaySession::factory()->for(Client::factory()->for($user))->create();
    Trial::factory()->for($session)->correct()->count(2)->create();
    Trial::factory()->for($session)->incorrect()->create();

    $this->actingAs($user)
        ->patch(route('play-sessions.finish', $session))
        ->assertRedirect(route('play-sessions.summary', $session));

    expect($session->refresh()->isFinished())->toBeTrue();

    $this->actingAs($user)
        ->get(route('play-sessions.summary', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->component('play/Summary')
            ->where('summary.total', 3)
            ->where('summary.correct', 2)
            ->where('summary.accuracy', 66.7),
        );
});

test('finishing a session without answers discards it', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $session = PlaySession::factory()->for($client)->create();

    $this->actingAs($user)
        ->patch(route('play-sessions.finish', $session))
        ->assertRedirect(route('clients.show', $client));

    $this->assertModelMissing($session);
});

test('session notes reject personal data', function () {
    $user = User::factory()->create();
    $session = PlaySession::factory()->for(Client::factory()->for($user))->finished()->create();

    $this->actingAs($user)
        ->patch(route('play-sessions.update', $session), ['notes' => 'Call mother at 012-3456789'])
        ->assertSessionHasErrors('notes');

    $this->actingAs($user)
        ->patch(route('play-sessions.update', $session), ['notes' => 'Good focus today'])
        ->assertSessionHasNoErrors();

    expect($session->refresh()->notes)->toBe('Good focus today');
});

test('clinicians cannot access another clinician\'s session', function (string $method, string $routeName) {
    $session = PlaySession::factory()->finished()->create();

    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName, $session), ['notes' => 'x'])
        ->assertForbidden();
})->with([
    'show' => ['GET', 'play-sessions.show'],
    'summary' => ['GET', 'play-sessions.summary'],
    'update' => ['PATCH', 'play-sessions.update'],
    'finish' => ['PATCH', 'play-sessions.finish'],
]);
