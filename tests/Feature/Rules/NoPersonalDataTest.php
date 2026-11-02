<?php

use App\Rules\NoPersonalData;
use Illuminate\Support\Facades\Validator;

function passesNoPersonalData(string $value): bool
{
    return Validator::make(['value' => $value], ['value' => [new NoPersonalData]])->passes();
}

test('it rejects text that looks like personal data', function (string $value) {
    expect(passesNoPersonalData($value))->toBeFalse();
})->with([
    'IC number with dashes' => '900101-14-5678',
    'IC number without dashes' => 'IC 900101145678',
    'mobile number' => 'call 012-345 6789',
    'mobile number with country code' => '+60123456789',
    'email address' => 'parent@example.com',
]);

test('it accepts references and clinical wording', function (string $value) {
    expect(passesNoPersonalData($value))->toBeTrue();
})->with([
    'clinician code' => 'C-01',
    'RN number' => 'RN 2024/0153',
    'diagnosis' => 'Speech sound disorder, fronting of /k/',
    'session note with numbers' => 'Scored 8/10 on 3 syllable words',
]);
