<?php

use App\Enums\ClinicalHours\HoursType;
use App\Enums\ClinicalHours\LogMode;
use App\Models\ClinicalLogEntry;
use App\Models\ClinicalLogForm;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function validLogFormPayload(array $overrides = []): array
{
    return [
        'mode' => 'log_form',
        'year' => 3,
        'semester' => 1,
        'session_start_year' => 2021,
        'days' => ['mon', 'wed'],
        'placement' => 'internal',
        'clinic_mode' => 'f2f',
        'placement_name' => 'Klinik Pertuturan UKM',
        'supervisor_name' => 'Dr. Aisyah',
        'practicum_type' => 'student_block',
        'hours_type' => 'hands_on',
        'entries' => [
            [
                'date' => '2021-10-04',
                'age_group' => 'child_adolescent',
                'rn_no' => 'RN1234',
                'session_mode' => 'face_to_face',
                'session_type' => 'therapy',
                'diagnosis' => 'Speech sound disorder',
                'hours' => 1,
                'minutes' => 30,
            ],
        ],
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function validSimplePayload(array $overrides = []): array
{
    return [
        'mode' => 'simple',
        'year' => 2,
        'semester' => 2,
        'session_start_year' => 2022,
        'practicum_type' => 'screening',
        'hours_type' => 'observation',
        'hours' => 12,
        'minutes' => 30,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('clinical-hours.index'))->assertRedirect(route('login'));
});

describe('index', function () {
    test('it shows only the student\'s own forms and totals', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->observation()->create();
        ClinicalLogEntry::factory()->for($form, 'form')->create(['duration_minutes' => 150]);
        ClinicalLogEntry::factory()->for(ClinicalLogForm::factory()->observation(), 'form')->create();

        $this->actingAs($user)
            ->get(route('clinical-hours.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('clinical-hours/Index')
                ->has('forms', 1)
                ->where('forms.0.id', $form->id)
                ->where('forms.0.total_minutes', 150)
                ->where('summary.totals.observation', 150),
            );
    });

    test('it counts the hours keyed in on simple forms', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->observation()->simple(200)->create();

        $this->actingAs($user)
            ->get(route('clinical-hours.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('forms.0.id', $form->id)
                ->where('forms.0.mode', 'simple')
                ->where('forms.0.total_minutes', 200)
                ->where('summary.totals.observation', 200),
            );
    });
});

describe('store', function () {
    test('it saves the form and converts session durations to minutes', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('clinical-hours.store'), validLogFormPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clinical-hours.index'));

        $form = $user->clinicalLogForms()->sole();
        expect($form->hours_type)->toBe(HoursType::HandsOn);
        expect($form->days)->toBe(['mon', 'wed']);
        expect($form->entries()->sole()->duration_minutes)->toBe(90);
    });

    test('it requires the form details and at least one session', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('clinical-hours.store'), [])
            ->assertSessionHasErrors([
                'year', 'semester', 'session_start_year', 'days', 'placement', 'clinic_mode',
                'placement_name', 'supervisor_name', 'practicum_type', 'hours_type',
                'entries' => 'Add at least one session to the log.',
            ]);
    });

    test('it rejects a session with no duration', function () {
        $payload = validLogFormPayload();
        $payload['entries'][0]['hours'] = 0;
        $payload['entries'][0]['minutes'] = 0;

        $this->actingAs(User::factory()->create())
            ->post(route('clinical-hours.store'), $payload)
            ->assertSessionHasErrors(['entries.0.minutes' => 'The session must last at least one minute.']);

        expect(ClinicalLogForm::count())->toBe(0);
    });

    test('it rejects minutes of 60 or more', function () {
        $payload = validLogFormPayload();
        $payload['entries'][0]['minutes'] = 60;

        $this->actingAs(User::factory()->create())
            ->post(route('clinical-hours.store'), $payload)
            ->assertSessionHasErrors('entries.0.minutes');
    });

    test('it saves keyed-in hours in simple mode without placement details or sessions', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('clinical-hours.store'), validSimplePayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clinical-hours.index'));

        $form = $user->clinicalLogForms()->sole();
        expect($form->mode)->toBe(LogMode::Simple);
        expect($form->simple_minutes)->toBe(750);
        expect($form->placement_name)->toBeNull();
        expect($form->days)->toBeNull();
        expect($form->entries()->count())->toBe(0);
    });

    test('it requires a duration in simple mode', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('clinical-hours.store'), validSimplePayload(['hours' => 0, 'minutes' => 0]))
            ->assertSessionHasErrors(['minutes' => 'Enter at least one minute of clinical hours.'])
            ->assertSessionDoesntHaveErrors(['placement_name', 'supervisor_name', 'days', 'entries']);

        expect(ClinicalLogForm::count())->toBe(0);
    });

    test('it rejects an unknown mode', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('clinical-hours.store'), validLogFormPayload(['mode' => 'quick']))
            ->assertSessionHasErrors('mode');
    });

    test('it rejects an unknown hours type', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('clinical-hours.store'), validLogFormPayload(['hours_type' => 'clinical']))
            ->assertSessionHasErrors('hours_type');
    });
});

describe('update', function () {
    test('it replaces the form details and sessions', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->observation()->create();
        ClinicalLogEntry::factory()->for($form, 'form')->count(3)->create();

        $this->actingAs($user)
            ->put(route('clinical-hours.update', $form), validLogFormPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clinical-hours.index'));

        expect($form->refresh()->hours_type)->toBe(HoursType::HandsOn);
        expect($form->entries()->pluck('duration_minutes')->all())->toBe([90]);
    });

    test('it keeps edited sessions as the same rows, removes dropped ones and adds new ones', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->create();
        [$kept, $dropped] = ClinicalLogEntry::factory()->for($form, 'form')->count(2)->create();
        $payload = validLogFormPayload();
        $newEntry = $payload['entries'][0];

        $this->actingAs($user)
            ->put(route('clinical-hours.update', $form), validLogFormPayload([
                'entries' => [
                    [...$newEntry, 'id' => $kept->id, 'hours' => 2, 'minutes' => 0],
                    [...$newEntry, 'id' => null, 'minutes' => 15],
                ],
            ]))
            ->assertSessionHasNoErrors();

        expect($kept->refresh()->duration_minutes)->toBe(120);
        $this->assertModelMissing($dropped);
        expect($form->entries()->pluck('duration_minutes', 'id')->all())->toHaveCount(2)->toHaveKey($kept->id);
    });

    test('it rejects session ids that belong to another form', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->create();
        $otherEntry = ClinicalLogEntry::factory()->create();
        $payload = validLogFormPayload();

        $this->actingAs($user)
            ->put(route('clinical-hours.update', $form), validLogFormPayload([
                'entries' => [[...$payload['entries'][0], 'id' => $otherEntry->id]],
            ]))
            ->assertSessionHasErrors('entries.0.id');

        expect($otherEntry->refresh()->clinical_log_form_id)->not->toBe($form->id);
    });

    test('switching a log form to simple mode removes its sessions and placement details', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->create();
        ClinicalLogEntry::factory()->for($form, 'form')->count(2)->create();

        $this->actingAs($user)
            ->put(route('clinical-hours.update', $form), validSimplePayload())
            ->assertSessionHasNoErrors();

        $form->refresh();
        expect($form->mode)->toBe(LogMode::Simple);
        expect($form->simple_minutes)->toBe(750);
        expect($form->supervisor_name)->toBeNull();
        expect($form->entries()->count())->toBe(0);
    });

    test('switching a simple form to log form mode clears the keyed-in hours', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->simple()->create();

        $this->actingAs($user)
            ->put(route('clinical-hours.update', $form), validLogFormPayload())
            ->assertSessionHasNoErrors();

        $form->refresh();
        expect($form->mode)->toBe(LogMode::LogForm);
        expect($form->simple_minutes)->toBeNull();
        expect($form->entries()->pluck('duration_minutes')->all())->toBe([90]);
    });
});

describe('edit', function () {
    test('it splits a simple form\'s minutes into hours and minutes', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->simple(135)->create();

        $this->actingAs($user)
            ->get(route('clinical-hours.edit', $form))
            ->assertInertia(fn (Assert $page) => $page
                ->component('clinical-hours/Edit')
                ->where('form.mode', 'simple')
                ->where('form.hours', 2)
                ->where('form.minutes', 15)
                ->where('form.placement', '')
                ->where('form.days', []),
            );
    });
});

describe('destroy', function () {
    test('it deletes the form and its sessions', function () {
        $user = User::factory()->create();
        $form = ClinicalLogForm::factory()->for($user)->create();
        ClinicalLogEntry::factory()->for($form, 'form')->create();

        $this->actingAs($user)
            ->delete(route('clinical-hours.destroy', $form))
            ->assertRedirect(route('clinical-hours.index'));

        $this->assertModelMissing($form);
        expect(ClinicalLogEntry::count())->toBe(0);
    });
});

test('students cannot access another student\'s form', function (string $method, string $routeName) {
    $form = ClinicalLogForm::factory()->create();

    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName, $form), validLogFormPayload())
        ->assertForbidden();

    expect($form->refresh()->exists)->toBeTrue();
})->with([
    'edit' => ['GET', 'clinical-hours.edit'],
    'update' => ['PUT', 'clinical-hours.update'],
    'destroy' => ['DELETE', 'clinical-hours.destroy'],
    'pdf' => ['GET', 'clinical-hours.pdf'],
]);
