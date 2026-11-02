<?php

use App\Models\ClinicalLogEntry;
use App\Models\ClinicalLogForm;
use App\Models\User;
use App\Services\ClinicalHoursSummary;

/**
 * @return array<string, mixed>
 */
function summarizeFor(User $user): array
{
    $forms = $user->clinicalLogForms()->withSum('entries', 'duration_minutes')->get();

    return (new ClinicalHoursSummary)->summarize($forms);
}

test('progress is measured against the configured targets', function () {
    config(['clinical_hours.targets' => ['observation' => 100, 'hands_on' => 250]]);

    $user = User::factory()->create();
    $observation = ClinicalLogForm::factory()->for($user)->observation()->create();
    ClinicalLogEntry::factory()->for($observation, 'form')->create(['duration_minutes' => 114 * 60]);
    $handsOn = ClinicalLogForm::factory()->for($user)->handsOn()->create();
    ClinicalLogEntry::factory()->for($handsOn, 'form')->count(2)->sequence(
        ['duration_minutes' => 200 * 60],
        ['duration_minutes' => 11 * 60],
    )->create();

    $categories = collect(summarizeFor($user)['categories'])->keyBy('hours_type');

    expect($categories['observation'])->toMatchArray([
        'target_minutes' => 6000,
        'completed_minutes' => 6840,
        'remaining_minutes' => 0,
        'percentage' => 114.0,
        'is_completed' => true,
    ]);
    expect($categories['hands_on'])->toMatchArray([
        'target_minutes' => 15000,
        'completed_minutes' => 12660,
        'remaining_minutes' => 2340,
        'percentage' => 84.4,
        'is_completed' => false,
    ]);
});

test('hours are grouped by year and semester, with latihan industri under internship', function () {
    $user = User::factory()->create();
    $semesterForm = ClinicalLogForm::factory()->for($user)->observation()->create(['year' => 3, 'semester' => 1]);
    ClinicalLogEntry::factory()->for($semesterForm, 'form')->create(['duration_minutes' => 90]);
    $internshipForm = ClinicalLogForm::factory()->for($user)->handsOn()->internship()->create(['year' => 3, 'semester' => 2]);
    ClinicalLogEntry::factory()->for($internshipForm, 'form')->create(['duration_minutes' => 125 * 60]);

    $periods = collect(summarizeFor($user)['periods'])->keyBy('key');

    expect($periods['y3s1']['minutes'])->toBe(['observation' => 90, 'hands_on' => 0]);
    expect($periods['internship']['minutes'])->toBe(['observation' => 0, 'hands_on' => 125 * 60]);
    expect($periods['y3s2']['minutes'])->toBe(['observation' => 0, 'hands_on' => 0]);
});

test('hours keyed in on simple forms are added to the log form totals', function () {
    $user = User::factory()->create();
    $logForm = ClinicalLogForm::factory()->for($user)->handsOn()->create(['year' => 2, 'semester' => 1]);
    ClinicalLogEntry::factory()->for($logForm, 'form')->create(['duration_minutes' => 60]);
    ClinicalLogForm::factory()->for($user)->handsOn()->simple(150)->create(['year' => 2, 'semester' => 1]);

    $summary = summarizeFor($user);

    expect($summary['totals']['hands_on'])->toBe(210);
    expect(collect($summary['periods'])->keyBy('key')['y2s1']['minutes'])->toBe(['observation' => 0, 'hands_on' => 210]);
});
