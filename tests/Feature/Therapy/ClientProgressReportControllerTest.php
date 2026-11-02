<?php

use App\Models\Client;
use App\Models\PlaySession;
use App\Models\Trial;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

/**
 * Capture the data passed to the progress report view.
 *
 * @return ArrayObject<string, mixed>
 */
function captureReportData(): ArrayObject
{
    $captured = new ArrayObject;

    View::composer('pdf.client-progress-report', function ($view) use ($captured): void {
        $captured->exchangeArray($view->getData());
    });

    return $captured;
}

test('the report downloads as a pdf named after the reference and date range', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['reference_code' => 'C-01']);

    $response = $this->actingAs($user)->get(route('clients.progress-report', [$client, 'from' => '2026-09-01', 'to' => '2026-10-31']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('PROGRESS REPORT - C-01 - 2026-09-01 TO 2026-10-31.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

test('the report defaults to the current semester', function () {
    $this->travelTo(Carbon::parse('2026-11-02'));
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['reference_code' => 'C-01']);

    $this->actingAs($user)
        ->get(route('clients.progress-report', $client))
        ->assertDownload('PROGRESS REPORT - C-01 - 2026-09-01 TO 2027-02-28.pdf');
});

test('the report identifies the client by reference only', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create([
        'reference_code' => 'C-01',
        'diagnosis' => 'Childhood apraxia of speech',
        'notes' => 'Prefers dinosaur pictures',
    ]);
    $captured = captureReportData();

    $this->actingAs($user)->get(route('clients.progress-report', [$client, 'from' => '2026-09-01', 'to' => '2026-10-31']))->assertOk();

    expect(array_keys($captured['client']))->toBe(['reference_code', 'age_group', 'language']);
    expect(view('pdf.client-progress-report', $captured->getArrayCopy())->render())
        ->toContain('C-01')
        ->not->toContain('Childhood apraxia of speech')
        ->not->toContain('Prefers dinosaur pictures')
        ->toContain('Reference only — contains no identifying patient information.');
});

test('the report only includes sessions within the date range', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $inside = PlaySession::factory()->for($client)->finished()->create(['started_at' => '2026-10-31 09:00', 'notes' => 'Good focus']);
    Trial::factory()->for($inside)->correct()->count(3)->create();
    $outside = PlaySession::factory()->for($client)->finished()->create(['started_at' => '2026-11-01 09:00']);
    Trial::factory()->for($outside)->incorrect()->count(3)->create();
    $captured = captureReportData();

    $this->actingAs($user)->get(route('clients.progress-report', [$client, 'from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk();

    expect($captured['sessions'])->toHaveCount(1);
    expect($captured['sessions'][0])->toMatchArray(['date' => '31/10/2026', 'total' => 3, 'accuracy' => 100.0, 'notes' => 'Good focus']);
    expect($captured['progress']['activities'][0]['sessions'])->toHaveCount(1);
});

test('the date range must not end before it starts', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('clients.progress-report', [$client, 'from' => '2026-10-31', 'to' => '2026-10-01']))
        ->assertSessionHasErrors('to');
});

test('clinicians cannot download another clinician\'s client report', function () {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('clients.progress-report', $client))
        ->assertForbidden();
});
