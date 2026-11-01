<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('clinical-hours:send-reminders')
    ->weeklyOn(config('clinical_hours.reminder.day'), config('clinical_hours.reminder.time'))
    ->timezone(config('clinical_hours.reminder.timezone'))
    ->onOneServer();
