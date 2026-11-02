<?php

namespace App\Services\Therapy;

use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use App\Models\Client;
use App\Models\PlaySession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ClinicalHoursDraft
{
    /**
     * Logged durations are rounded to this many minutes.
     */
    private const int ROUND_TO_MINUTES = 5;

    /**
     * Get the client's finished sessions on the date that have not been added to clinical hours, earliest first.
     *
     * @return Collection<int, PlaySession>
     */
    public function unloggedSessions(Client $client, string $date): Collection
    {
        return $this->sessionsOn($client, $date)->whereNull('clinical_log_entry_id')->get();
    }

    /**
     * Get the client's finished sessions on the date that are already in clinical hours, earliest first.
     *
     * @return Collection<int, PlaySession>
     */
    public function loggedSessions(Client $client, string $date): Collection
    {
        return $this->sessionsOn($client, $date)->whereNotNull('clinical_log_entry_id')->get();
    }

    /**
     * Pre-fill a clinical log entry for one clinic visit made up of the given sessions.
     *
     * The duration runs from the first session's start to the last session's end,
     * rounded to the nearest 5 minutes.
     *
     * @param  Collection<int, PlaySession>  $sessions
     * @return array{date: string, age_group: string, rn_no: string, session_mode: string, session_type: string, diagnosis: string, hours: int, minutes: int}
     */
    public function entryFor(Client $client, string $date, Collection $sessions): array
    {
        $minutes = 0;

        if ($sessions->isNotEmpty()) {
            $start = $sessions->min('started_at');
            $end = $sessions->max('ended_at');
            $minutes = (int) round($start->diffInMinutes($end) / self::ROUND_TO_MINUTES) * self::ROUND_TO_MINUTES;
        }

        $minutes = max(self::ROUND_TO_MINUTES, $minutes);

        return [
            'date' => $date,
            'age_group' => $client->age_group->value,
            'rn_no' => $client->reference_code,
            'session_mode' => SessionMode::FaceToFace->value,
            'session_type' => SessionType::Therapy->value,
            'diagnosis' => $client->diagnosis ?? '',
            'hours' => intdiv($minutes, 60),
            'minutes' => $minutes % 60,
        ];
    }

    /**
     * Start a query for the client's finished sessions that started on the date.
     *
     * @return HasMany<PlaySession, Client>
     */
    private function sessionsOn(Client $client, string $date): HasMany
    {
        $day = Carbon::parse($date);

        return $client->playSessions()
            ->reorder()
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->with('activity:id,title')
            ->orderBy('started_at')
            ->orderBy('id');
    }
}
