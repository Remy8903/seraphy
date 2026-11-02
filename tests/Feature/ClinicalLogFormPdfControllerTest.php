<?php

use App\Models\ClinicalLogEntry;
use App\Models\ClinicalLogForm;
use App\Models\User;

test('it downloads the log form as a pdf named after the student', function () {
    $user = User::factory()->create(['name' => 'Sarah Aminah', 'matric_no' => 'A180123']);
    $form = ClinicalLogForm::factory()->for($user)->observation()->create([
        'year' => 3,
        'semester' => 1,
        'session_start_year' => 2021,
    ]);
    ClinicalLogEntry::factory()->for($form, 'form')->count(30)->create();

    $response = $this->actingAs($user)->get(route('clinical-hours.pdf', $form));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('KSP CLINICAL LOG HOURS FORM SEM 1 2122 - OBSERVATION-A180123-SARAH AMINAH-YEAR 3.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

test('simple forms have no pdf to download', function () {
    $user = User::factory()->create();
    $form = ClinicalLogForm::factory()->for($user)->simple()->create();

    $this->actingAs($user)->get(route('clinical-hours.pdf', $form))->assertNotFound();
});
