<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\ClinicalHoursSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClinicalLogReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $forms = $notifiable->clinicalLogForms()->withSum('entries', 'duration_minutes')->get();
        $summary = app(ClinicalHoursSummary::class)->summarize($forms);

        $message = (new MailMessage)
            ->subject('Reminder: fill in your clinical log form')
            ->greeting("Hi {$notifiable->name},")
            ->line("Don't forget to log this week's clinical sessions while the details are still fresh.")
            ->line('Your progress so far:');

        foreach ($summary['categories'] as $category) {
            $progress = "{$category['label']}: {$this->formatDuration($category['completed_minutes'])} of {$this->formatDuration($category['target_minutes'])}";

            $message->line($category['is_completed']
                ? "{$progress} (completed)"
                : "{$progress} ({$this->formatDuration($category['remaining_minutes'])} remaining)");
        }

        return $message
            ->action('Fill in log form', route('clinical-hours.create'))
            ->line("Remember to download the PDF and get your supervisor's signature.");
    }

    /**
     * Format a number of minutes as hours and minutes, e.g. 150 -> "2h 30m".
     */
    private function formatDuration(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return $minutes === 0 ? "{$hours}h" : "{$hours}h {$minutes}m";
    }
}
