<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\ClinicalLogForm;
use App\Models\PlaySession;
use App\Models\TherapyGoal;
use App\Models\Trial;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows only the clinician\'s own clients, sessions and goals', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['reference_code' => 'C-01']);
    Client::factory()->for($user)->archived()->create();
    TherapyGoal::factory()->for($client)->create(['description' => 'Say /s/ at the start of words']);
    TherapyGoal::factory()->for($client)->achieved()->create();
    PlaySession::factory()->for($client)->for(Activity::factory()->for($user))->finished()->create();

    $otherClient = Client::factory()->create(['reference_code' => 'OTHER-1']);
    TherapyGoal::factory()->for($otherClient)->create();
    PlaySession::factory()->for($otherClient)->finished()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('counts.active_clients', 1)
            ->where('counts.activities', 1)
            ->where('sessions.total', 1)
            ->has('recentSessions', 1)
            ->where('recentSessions.0.client_reference', 'C-01')
            ->where('goals.active_count', 1)
            ->where('goals.achieved_count', 1)
            ->where('goals.active.0.description', 'Say /s/ at the start of words'),
        );
});

test('finished sessions are counted per week and answers give the recent accuracy', function () {
    $this->travelTo(Carbon::parse('2026-11-04 10:00'));
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $thisWeek = PlaySession::factory()->for($client)->create(['ended_at' => now()]);
    Trial::factory()->for($thisWeek)->correct()->count(3)->create();
    Trial::factory()->for($thisWeek)->incorrect()->create();
    PlaySession::factory()->for($client)->create(['ended_at' => now()->subWeeks(2)]);
    PlaySession::factory()->for($client)->create(['ended_at' => now()->subWeeks(10)]);
    PlaySession::factory()->for($client)->create(['ended_at' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sessions.this_week', 1)
            ->where('sessions.total', 3)
            ->where('sessions.answers_last_30_days', 4)
            ->where('sessions.accuracy_last_30_days', 75)
            ->where('recentSessions.0.accuracy', 75)
            ->where('weeklySessions', fn ($weeks) => collect($weeks)->pluck('sessions')->all() === [0, 0, 0, 0, 0, 1, 0, 1]),
        );
});

test('clinical hours progress is shown against each target', function () {
    $user = User::factory()->create();
    ClinicalLogForm::factory()->for($user)->observation()->simple(180)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('hours.forms_count', 1)
            ->where('hours.categories.0.hours_type', 'observation')
            ->where('hours.categories.0.completed_minutes', 180),
        );
});
