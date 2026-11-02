<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\PlaySession;
use App\Models\Trial;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function validClientPayload(array $overrides = []): array
{
    return [
        'reference_code' => 'C-01',
        'age_group' => 'child_adolescent',
        'language' => 'ms',
        'diagnosis' => 'Speech sound disorder',
        'notes' => '',
        'confirms_no_personal_data' => true,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
});

test('the index lists only the clinician\'s active clients', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    Client::factory()->for($user)->archived()->create();
    Client::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/Index')
            ->has('clients', 1)
            ->where('clients.0.id', $client->id),
        );
});

test('a client is stored by reference only', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('clients.store'), validClientPayload());

    $client = $user->clients()->sole();
    $response->assertRedirect(route('clients.show', $client));
    expect($client->reference_code)->toBe('C-01');
});

test('a client cannot be stored without confirming there is no personal data', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('clients.store'), validClientPayload(['confirms_no_personal_data' => false]))
        ->assertSessionHasErrors('confirms_no_personal_data');

    expect(Client::count())->toBe(0);
});

test('personal data is rejected in client fields', function (string $field, string $value) {
    $this->actingAs(User::factory()->create())
        ->post(route('clients.store'), validClientPayload([$field => $value]))
        ->assertSessionHasErrors($field);
})->with([
    'IC number as reference' => ['reference_code', '900101-14-5678'],
    'phone number in notes' => ['notes', 'Mother: 012-345 6789'],
    'email in diagnosis' => ['diagnosis', 'see parent@example.com'],
]);

test('a reference must be unique for the clinician only', function () {
    $user = User::factory()->create();
    Client::factory()->for($user)->create(['reference_code' => 'C-01']);
    Client::factory()->create(['reference_code' => 'C-02']);

    $this->actingAs($user)
        ->post(route('clients.store'), validClientPayload())
        ->assertSessionHasErrors(['reference_code' => 'You already have a client with this reference.']);

    $this->actingAs($user)
        ->post(route('clients.store'), validClientPayload(['reference_code' => 'C-02']))
        ->assertSessionHasNoErrors();
});

test('updating a client does not require the confirmation again', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['reference_code' => 'C-01']);

    $this->actingAs($user)
        ->put(route('clients.update', $client), validClientPayload(['reference_code' => 'C-01', 'confirms_no_personal_data' => null]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('clients.show', $client));
});

test('the client page shows the plan, sessions and progress', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $assigned = Activity::factory()->for($user)->create();
    $unassigned = Activity::factory()->for($user)->create();
    $client->activities()->attach($assigned);
    $session = PlaySession::factory()->for($client)->for($assigned)->finished()->create();
    Trial::factory()->for($session)->correct()->count(3)->create();
    Trial::factory()->for($session)->incorrect()->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/Show')
            ->has('activities', 1)
            ->where('activities.0.id', $assigned->id)
            ->has('unassignedActivities', 1)
            ->where('unassignedActivities.0.id', $unassigned->id)
            ->where('sessions.0.accuracy', 75)
            ->has('progress.activities', 1),
        );
});

test('archiving toggles the client between active and archived', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)->patch(route('clients.archive', $client));
    expect($client->refresh()->isArchived())->toBeTrue();

    $this->actingAs($user)->patch(route('clients.archive', $client));
    expect($client->refresh()->isArchived())->toBeFalse();
});

test('deleting a client removes their sessions and answers', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $session = PlaySession::factory()->for($client)->create();
    Trial::factory()->for($session)->create();

    $this->actingAs($user)
        ->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'));

    $this->assertModelMissing($client);
    expect(PlaySession::count())->toBe(0);
    expect(Trial::count())->toBe(0);
});

test('activities can be assigned to and removed from a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $activity = Activity::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('clients.activities.store', $client), ['activity_id' => $activity->id])
        ->assertSessionHasNoErrors();
    expect($client->activities()->pluck('activities.id')->all())->toBe([$activity->id]);

    $this->actingAs($user)
        ->delete(route('clients.activities.destroy', [$client, $activity]));
    expect($client->activities()->count())->toBe(0);
});

test('another clinician\'s activity cannot be assigned', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('clients.activities.store', $client), ['activity_id' => Activity::factory()->create()->id])
        ->assertSessionHasErrors('activity_id');
});

test('clinicians cannot access another clinician\'s client', function (string $method, string $routeName) {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName, $client), validClientPayload(['activity_id' => 1]))
        ->assertForbidden();

    expect($client->refresh()->exists)->toBeTrue();
})->with([
    'show' => ['GET', 'clients.show'],
    'edit' => ['GET', 'clients.edit'],
    'update' => ['PUT', 'clients.update'],
    'destroy' => ['DELETE', 'clients.destroy'],
    'archive' => ['PATCH', 'clients.archive'],
    'assign' => ['POST', 'clients.activities.store'],
]);
