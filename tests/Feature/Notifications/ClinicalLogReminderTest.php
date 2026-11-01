<?php

use App\Models\ClinicalLogEntry;
use App\Models\ClinicalLogForm;
use App\Models\User;
use App\Notifications\ClinicalLogReminder;

test('the reminder links to a new log form and shows the student\'s progress', function () {
    config(['clinical_hours.targets' => ['observation' => 100, 'hands_on' => 250]]);

    $user = User::factory()->create(['name' => 'Sarah']);
    $observation = ClinicalLogForm::factory()->for($user)->observation()->create();
    ClinicalLogEntry::factory()->for($observation, 'form')->create(['duration_minutes' => 114 * 60]);
    $handsOn = ClinicalLogForm::factory()->for($user)->handsOn()->create();
    ClinicalLogEntry::factory()->for($handsOn, 'form')->create(['duration_minutes' => 211 * 60 + 30]);

    $mail = (new ClinicalLogReminder)->toMail($user);

    expect($mail->subject)->toBe('Reminder: fill in your clinical log form');
    expect($mail->greeting)->toBe('Hi Sarah,');
    expect($mail->actionUrl)->toBe(route('clinical-hours.create'));
    expect($mail->introLines)->toContain(
        'Observation: 114h of 100h (completed)',
        'Hands-On: 211h 30m of 250h (38h 30m remaining)',
    );
});
