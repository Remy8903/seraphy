<?php

use App\Models\User;
use App\Notifications\ClinicalLogReminder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

test('it reminds verified students only', function () {
    Notification::fake();
    $verified = User::factory()->count(2)->create();
    $unverified = User::factory()->unverified()->create();

    $this->artisan('clinical-hours:send-reminders')
        ->expectsOutput('Queued clinical log reminders for 2 students.')
        ->assertSuccessful();

    Notification::assertSentTo($verified, ClinicalLogReminder::class);
    Notification::assertNotSentTo($unverified, ClinicalLogReminder::class);
});

test('it is scheduled every sunday at 8pm malaysia time', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains($event->command, 'clinical-hours:send-reminders'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 20 * * 0');
    expect($event->timezone)->toBe('Asia/Kuala_Lumpur');
});
