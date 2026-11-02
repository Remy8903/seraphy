<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\User;

test('an assigned activity can be saved for offline play with only the client reference', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['reference_code' => 'C-07', 'diagnosis' => 'Speech sound disorder']);
    $activity = Activity::factory()->for($user)->create();
    $word = ContentItem::factory()->starter()->create();
    $activity->items()->create(['content_item_id' => $word->id, 'position' => 0]);
    $client->activities()->attach($activity);

    $response = $this->actingAs($user)
        ->getJson(route('clients.activities.offline', [$client, $activity]))
        ->assertOk()
        ->assertJsonPath('client', ['id' => $client->id, 'reference_code' => 'C-07'])
        ->assertJsonPath('activity.id', $activity->id)
        ->assertJsonPath('cards.0.word.id', $word->id);

    expect($response->getContent())->not->toContain('Speech sound disorder');
});

test('only an assigned activity of the clinician\'s own client can be saved offline', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $unassigned = Activity::factory()->for($user)->create();
    $otherClient = Client::factory()->create();
    $otherActivity = Activity::factory()->for($otherClient->user)->create();
    $otherClient->activities()->attach($otherActivity);

    $this->actingAs($user)
        ->getJson(route('clients.activities.offline', [$client, $unassigned]))
        ->assertNotFound();

    $this->actingAs($user)
        ->getJson(route('clients.activities.offline', [$otherClient, $otherActivity]))
        ->assertForbidden();
});
