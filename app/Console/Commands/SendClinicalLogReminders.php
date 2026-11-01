<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ClinicalLogReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

#[Signature('clinical-hours:send-reminders')]
#[Description('Email every verified student a reminder to fill in their clinical log form')]
class SendClinicalLogReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->whereNotNull('email_verified_at')
            ->chunkById(100, function (Collection $users) use (&$sent): void {
                Notification::send($users, new ClinicalLogReminder);

                $sent += $users->count();
            });

        $this->info("Queued clinical log reminders for {$sent} students.");

        return self::SUCCESS;
    }
}
