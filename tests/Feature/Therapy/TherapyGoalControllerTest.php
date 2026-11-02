<?php

use App\Enums\Therapy\GoalStatus;
use App\Models\Client;
use App\Models\PlaySession;
use App\Models\TherapyGoal;
use App\Models\Trial;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validGoalPayload(array $overrides = []): array
{
    return [
        'description' => '/s/ initial in words, 80% across 3 sessions',
        'game_type' => 'sound_drill',
        'language' => 'ms',
        'target_sound' => 's',
        'word_position' => 'initial',
        'target_accuracy' => 80,
        'consecutive_sessions' => 3,
        'min_trials_per_session' => 10,
        ...$overrides,
    ];
}

test('a clinician can add a goal to their client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('clients.goals.store', $client), validGoalPayload())
        ->assertRedirect(route('clients.show', $client))
        ->assertInertiaFlash('toast.message', 'Goal added.');

    expect($client->goals()->sole())
        ->status->toBe(GoalStatus::Active)
        ->target_sound->toBe('s')
        ->target_accuracy->toBe(80);
});

test('a new goal the client has already reached is marked achieved', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $session = PlaySession::factory()->for($client)->finished()->create();
    Trial::factory()->for($session)->correct()->count(10)->create();

    $this->actingAs($user)
        ->post(route('clients.goals.store', $client), validGoalPayload([
            'game_type' => null,
            'language' => null,
            'target_sound' => null,
            'word_position' => null,
            'consecutive_sessions' => 1,
        ]))
        ->assertInertiaFlash('toast.message', 'Goal added. The client has already reached it.');

    expect($client->goals()->sole()->status)->toBe(GoalStatus::Achieved);
});

test('goal fields are validated', function (array $overrides, string $field) {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('clients.goals.store', $client), validGoalPayload($overrides))
        ->assertSessionHasErrors($field);

    expect($client->goals()->count())->toBe(0);
})->with([
    'personal data in description' => [['description' => 'Ali bin Abu, call 012-3456789'], 'description'],
    'uppercase target sound' => [['target_sound' => 'S'], 'target_sound'],
    'accuracy above 100' => [['target_accuracy' => 120], 'target_accuracy'],
    'zero consecutive sessions' => [['consecutive_sessions' => 0], 'consecutive_sessions'],
]);

test('changing the criteria of an achieved goal reopens it', function () {
    $user = User::factory()->create();
    $goal = TherapyGoal::factory()->for(Client::factory()->for($user))->achieved()->create();

    $this->actingAs($user)
        ->put(route('goals.update', $goal), validGoalPayload(['target_accuracy' => 95]))
        ->assertRedirect(route('clients.show', $goal->client_id));

    expect($goal->refresh())
        ->status->toBe(GoalStatus::Active)
        ->achieved_at->toBeNull()
        ->target_accuracy->toBe(95);
});

test('rewording an achieved goal keeps it achieved', function () {
    $user = User::factory()->create();
    $goal = TherapyGoal::factory()->for(Client::factory()->for($user))->achieved()->create([
        'game_type' => null,
        'language' => null,
        'target_sound' => null,
        'word_position' => null,
    ]);

    $this->actingAs($user)->put(route('goals.update', $goal), validGoalPayload([
        'description' => 'Reworded goal',
        'game_type' => null,
        'language' => null,
        'target_sound' => null,
        'word_position' => null,
        'target_accuracy' => $goal->target_accuracy,
        'consecutive_sessions' => $goal->consecutive_sessions,
        'min_trials_per_session' => $goal->min_trials_per_session,
    ]));

    expect($goal->refresh())
        ->description->toBe('Reworded goal')
        ->status->toBe(GoalStatus::Achieved);
});

test('an active goal can be discontinued and resumed', function () {
    $user = User::factory()->create();
    $goal = TherapyGoal::factory()->for(Client::factory()->for($user))->create();

    $this->actingAs($user)->patch(route('goals.status', $goal))->assertInertiaFlash('toast.message', 'Goal discontinued.');
    expect($goal->refresh()->status)->toBe(GoalStatus::Discontinued);

    $this->actingAs($user)->patch(route('goals.status', $goal))->assertInertiaFlash('toast.message', 'Goal resumed.');
    expect($goal->refresh()->status)->toBe(GoalStatus::Active);
});

test('an achieved goal cannot be discontinued', function () {
    $user = User::factory()->create();
    $goal = TherapyGoal::factory()->for(Client::factory()->for($user))->achieved()->create();

    $this->actingAs($user)->patch(route('goals.status', $goal))->assertConflict();

    expect($goal->refresh()->status)->toBe(GoalStatus::Achieved);
});

test('a goal can be deleted', function () {
    $user = User::factory()->create();
    $goal = TherapyGoal::factory()->for(Client::factory()->for($user))->create();

    $this->actingAs($user)->delete(route('goals.destroy', $goal))->assertRedirect(route('clients.show', $goal->client_id));

    $this->assertModelMissing($goal);
});

test('clinicians cannot change another clinician\'s goals', function (string $method, string $routeName) {
    $goal = TherapyGoal::factory()->create();

    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName, $goal), validGoalPayload())
        ->assertForbidden();

    expect($goal->refresh()->status)->toBe(GoalStatus::Active);
})->with([
    'update' => ['PUT', 'goals.update'],
    'status' => ['PATCH', 'goals.status'],
    'destroy' => ['DELETE', 'goals.destroy'],
]);

test('clinicians cannot add goals to another clinician\'s client', function () {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('clients.goals.store', $client), validGoalPayload())
        ->assertForbidden();

    expect($client->goals()->count())->toBe(0);
});

test('finishing a session celebrates goals it achieves', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    TherapyGoal::factory()->for($client)->create(['description' => 'Say /s/ clearly', 'consecutive_sessions' => 1]);
    $session = PlaySession::factory()->for($client)->create();
    Trial::factory()->for($session)->correct()->count(10)->create();

    $this->actingAs($user)
        ->patch(route('play-sessions.finish', $session))
        ->assertRedirect(route('play-sessions.summary', $session))
        ->assertInertiaFlash('achievedGoals', ['Say /s/ clearly']);

    $this->actingAs($user)
        ->get(route('play-sessions.summary', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('goals.0.description', 'Say /s/ clearly')
            ->where('goals.0.status', 'achieved'),
        );
});

test('the client page shows each goal\'s progress', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    TherapyGoal::factory()->for($client)->create(['consecutive_sessions' => 3, 'target_accuracy' => 80]);
    $session = PlaySession::factory()->for($client)->finished()->create();
    Trial::factory()->for($session)->correct()->count(10)->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertInertia(fn (Assert $page) => $page
            ->where('goals.0.streak', 1)
            ->where('goals.0.consecutive_sessions', 3)
            ->has('goals.0.recent_sessions', 1),
        );
});
