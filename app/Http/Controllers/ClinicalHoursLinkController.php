<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\ClinicalHours\LogMode;
use App\Enums\ClinicalHours\SessionMode;
use App\Enums\ClinicalHours\SessionType;
use App\Http\Requests\AddToClinicalHoursRequest;
use App\Models\Client;
use App\Models\ClinicalLogForm;
use App\Models\PlaySession;
use App\Services\Therapy\ClinicalHoursDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClinicalHoursLinkController extends Controller
{
    /**
     * Show the pre-filled clinical log entry for a day of game sessions with a client.
     */
    public function create(Request $request, Client $client, ClinicalHoursDraft $draft): Response
    {
        Gate::authorize('update', $client);

        $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];

        $sessions = $draft->unloggedSessions($client, $date);

        $forms = $request->user()
            ->clinicalLogForms()
            ->where('mode', LogMode::LogForm)
            ->orderByDesc('session_start_year')
            ->orderByDesc('semester')
            ->latest('id')
            ->get();

        return Inertia::render('clients/AddToClinicalHours', [
            'client' => [
                'id' => $client->id,
                'reference_code' => $client->reference_code,
            ],
            'date' => $date,
            'sessions' => $sessions->map(fn (PlaySession $session): array => $this->sessionRow($session)),
            'loggedSessions' => $draft->loggedSessions($client, $date)
                ->load('clinicalLogEntry:id,clinical_log_form_id')
                ->map(fn (PlaySession $session): array => [
                    ...$this->sessionRow($session),
                    'clinical_log_form_id' => $session->clinicalLogEntry->clinical_log_form_id,
                ]),
            'forms' => $forms->map(fn (ClinicalLogForm $form): array => [
                'id' => $form->id,
                'label' => collect([
                    "Year {$form->year} Sem {$form->semester} {$form->sessionLabel()}",
                    $form->hours_type->label(),
                    $form->placement_name,
                ])->filter()->implode(' · '),
            ]),
            'entry' => [
                'clinical_log_form_id' => $forms->first()?->id,
                ...$draft->entryFor($client, $date, $sessions),
            ],
            'options' => [
                'ageGroups' => AgeGroup::options(),
                'sessionModes' => SessionMode::options(),
                'sessionTypes' => SessionType::options(),
            ],
        ]);
    }

    /**
     * Add a day's game sessions with a client to a clinical log form as one entry.
     */
    public function store(AddToClinicalHoursRequest $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        DB::transaction(function () use ($request): void {
            $form = ClinicalLogForm::query()->findOrFail($request->integer('clinical_log_form_id'));

            $entry = $form->entries()->create($request->entryAttributes());

            PlaySession::query()
                ->whereKey($request->unloggedSessionIds())
                ->update(['clinical_log_entry_id' => $entry->id]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added to clinical hours.')]);

        return to_route('clients.show', $client);
    }

    /**
     * Describe a session in the list of sessions being logged.
     *
     * @return array{id: int, activity_title: string, started_at: string, ended_at: string|null}
     */
    private function sessionRow(PlaySession $session): array
    {
        return [
            'id' => $session->id,
            'activity_title' => $session->activity->title,
            'started_at' => $session->started_at->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
        ];
    }
}
