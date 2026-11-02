<?php

use App\Enums\ClinicalHours\AgeGroup;
use App\Models\Client;
use App\Models\ClinicalLogEntry;
use App\Models\ClinicalLogForm;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create a finished session with the client between two times.
 */
function sessionBetween(Client $client, string $start, string $end): PlaySession
{
    return PlaySession::factory()->for($client)->create([
        'started_at' => Carbon::parse($start),
        'ended_at' => Carbon::parse($end),
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function addToClinicalHoursPayload(ClinicalLogForm $form, array $overrides = []): array
{
    return [
        'date' => '2026-10-05',
        'clinical_log_form_id' => $form->id,
        'age_group' => 'child_adolescent',
        'rn_no' => 'C-01',
        'session_mode' => 'face_to_face',
        'session_type' => 'therapy',
        'diagnosis' => 'Speech sound disorder',
        'hours' => 0,
        'minutes' => 50,
        ...$overrides,
    ];
}

test('the entry is pre-filled from the client and the day\'s sessions', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create([
        'reference_code' => 'C-01',
        'age_group' => AgeGroup::ChildAdolescent,
        'diagnosis' => 'Speech sound disorder',
    ]);
    $first = sessionBetween($client, '2026-10-05 01:00', '2026-10-05 01:20');
    $second = sessionBetween($client, '2026-10-05 01:30', '2026-10-05 01:52');
    sessionBetween($client, '2026-10-06 01:00', '2026-10-06 03:00');
    ClinicalLogForm::factory()->for($user)->create(['session_start_year' => 2025]);
    $latestForm = ClinicalLogForm::factory()->for($user)->create(['session_start_year' => 2026, 'semester' => 1]);
    ClinicalLogForm::factory()->for($user)->simple()->create(['session_start_year' => 2027]);

    $this->actingAs($user)
        ->get(route('clients.clinical-hours.create', [$client, 'date' => '2026-10-05']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/AddToClinicalHours')
            ->where('sessions.0.id', $first->id)
            ->where('sessions.1.id', $second->id)
            ->has('sessions', 2)
            ->has('forms', 2)
            ->where('entry', [
                'clinical_log_form_id' => $latestForm->id,
                'date' => '2026-10-05',
                'age_group' => 'child_adolescent',
                'rn_no' => 'C-01',
                'session_mode' => 'face_to_face',
                'session_type' => 'therapy',
                'diagnosis' => 'Speech sound disorder',
                'hours' => 0,
                'minutes' => 50,
            ]),
        );
});

test('a day\'s sessions are added to the log form as one entry', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $sameDay = [
        sessionBetween($client, '2026-10-05 01:00', '2026-10-05 01:20'),
        sessionBetween($client, '2026-10-05 01:30', '2026-10-05 01:52'),
    ];
    $otherDay = sessionBetween($client, '2026-10-06 01:00', '2026-10-06 01:30');
    $form = ClinicalLogForm::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('clients.clinical-hours.store', $client), addToClinicalHoursPayload($form, ['hours' => 1, 'minutes' => 5]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('clients.show', $client));

    $entry = $form->entries()->sole();
    expect($entry->duration_minutes)->toBe(65);
    expect($entry->date->toDateString())->toBe('2026-10-05');
    expect($entry->playSessions()->pluck('id')->sort()->values()->all())
        ->toBe(collect($sameDay)->pluck('id')->sort()->values()->all());
    expect($otherDay->refresh()->clinical_log_entry_id)->toBeNull();
});

test('sessions cannot be added to a simple form or another clinician\'s form', function (Closure $makeForm) {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $session = sessionBetween($client, '2026-10-05 01:00', '2026-10-05 01:20');
    $form = $makeForm($user);

    $this->actingAs($user)
        ->post(route('clients.clinical-hours.store', $client), addToClinicalHoursPayload($form))
        ->assertSessionHasErrors(['clinical_log_form_id' => 'Choose one of your log forms. Simple-mode forms cannot hold sessions.']);

    expect(ClinicalLogEntry::count())->toBe(0);
    expect($session->refresh()->clinical_log_entry_id)->toBeNull();
})->with([
    'simple form' => [fn (User $user) => ClinicalLogForm::factory()->for($user)->simple()->create()],
    'another clinician\'s form' => [fn (User $user) => ClinicalLogForm::factory()->create()],
]);

test('a day that is already in clinical hours cannot be added again', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $form = ClinicalLogForm::factory()->for($user)->create();
    $entry = ClinicalLogEntry::factory()->for($form, 'form')->create();
    sessionBetween($client, '2026-10-05 01:00', '2026-10-05 01:20')->forceFill(['clinical_log_entry_id' => $entry->id])->save();

    $this->actingAs($user)
        ->post(route('clients.clinical-hours.store', $client), addToClinicalHoursPayload($form))
        ->assertSessionHasErrors('date');

    expect($form->entries()->count())->toBe(1);
});

test('clinicians cannot add another clinician\'s client to clinical hours', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    sessionBetween($client, '2026-10-05 01:00', '2026-10-05 01:20');
    $form = ClinicalLogForm::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('clients.clinical-hours.create', [$client, 'date' => '2026-10-05']))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('clients.clinical-hours.store', $client), addToClinicalHoursPayload($form))
        ->assertForbidden();

    expect(ClinicalLogEntry::count())->toBe(0);
});

test('the link survives editing the log form and is cleared when the entry is removed', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $session = sessionBetween($client, '2026-10-05 01:00', '2026-10-05 01:20');
    $form = ClinicalLogForm::factory()->for($user)->create();
    $this->actingAs($user)->post(route('clients.clinical-hours.store', $client), addToClinicalHoursPayload($form));
    $entry = $form->entries()->sole();
    $entryPayload = [
        'id' => $entry->id,
        'date' => '2026-10-05',
        'age_group' => 'child_adolescent',
        'rn_no' => 'C-01',
        'session_mode' => 'face_to_face',
        'session_type' => 'therapy',
        'diagnosis' => 'Speech sound disorder',
        'hours' => 1,
        'minutes' => 0,
    ];

    $formPayload = [
        'mode' => 'log_form',
        'year' => $form->year,
        'semester' => $form->semester,
        'session_start_year' => $form->session_start_year,
        'days' => $form->days,
        'placement' => $form->placement->value,
        'clinic_mode' => $form->clinic_mode->value,
        'placement_name' => $form->placement_name,
        'supervisor_name' => $form->supervisor_name,
        'practicum_type' => $form->practicum_type->value,
        'hours_type' => $form->hours_type->value,
    ];

    $this->actingAs($user)
        ->put(route('clinical-hours.update', $form), [...$formPayload, 'entries' => [$entryPayload]])
        ->assertSessionHasNoErrors();

    expect($session->refresh()->clinical_log_entry_id)->toBe($entry->id);
    expect($entry->refresh()->duration_minutes)->toBe(60);

    $this->actingAs($user)
        ->put(route('clinical-hours.update', $form), [...$formPayload, 'entries' => [[...$entryPayload, 'id' => null]]])
        ->assertSessionHasNoErrors();

    expect($session->refresh()->clinical_log_entry_id)->toBeNull();
});
