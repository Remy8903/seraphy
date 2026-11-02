<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalHours\AgeGroup;
use App\Enums\Therapy\GameType;
use App\Enums\Therapy\Language;
use App\Enums\Therapy\TrialResult;
use App\Enums\Therapy\WordPosition;
use App\Http\Requests\ClientRequest;
use App\Models\Activity;
use App\Models\Client;
use App\Models\PlaySession;
use App\Services\Therapy\ClientProgress;
use App\Services\Therapy\GoalEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Show the clinician's clients, or their archived clients.
     */
    public function index(Request $request): Response
    {
        $showingArchived = $request->boolean('archived');

        $clients = $request->user()
            ->clients()
            ->when(
                $showingArchived,
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->active(),
            )
            ->withCount('activities')
            ->withMax('playSessions', 'started_at')
            ->orderBy('reference_code')
            ->get();

        return Inertia::render('clients/Index', [
            'showingArchived' => $showingArchived,
            'clients' => $clients->map(fn (Client $client): array => [
                'id' => $client->id,
                'reference_code' => $client->reference_code,
                'age_group' => $client->age_group->label(),
                'language' => $client->language->label(),
                'diagnosis' => $client->diagnosis,
                'activities_count' => (int) $client->getAttribute('activities_count'),
                'last_played_at' => $client->getAttribute('play_sessions_max_started_at'),
                'is_archived' => $client->isArchived(),
            ]),
        ]);
    }

    /**
     * Show the page for adding a client.
     */
    public function create(): Response
    {
        return Inertia::render('clients/Create', [
            'options' => $this->options(),
        ]);
    }

    /**
     * Store a new client.
     */
    public function store(ClientRequest $request): RedirectResponse
    {
        $client = $request->user()->clients()->create($request->clientAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client added.')]);

        return to_route('clients.show', $client);
    }

    /**
     * Show a client's therapy plan, sessions and progress.
     */
    public function show(Request $request, Client $client, ClientProgress $progress, GoalEvaluator $goals): Response
    {
        Gate::authorize('view', $client);

        $assignedActivities = $client->activities()->withCount('items')->orderBy('title')->get();

        $unassignedActivities = $request->user()
            ->activities()
            ->whereNotIn('id', $assignedActivities->modelKeys())
            ->orderBy('title')
            ->get(['id', 'title', 'game_type', 'language']);

        $sessions = $client->playSessions()
            ->with(['activity:id,title', 'clinicalLogEntry:id,clinical_log_form_id'])
            ->withCount([
                'trials',
                'trials as correct_count' => fn ($query) => $query->where('result', TrialResult::Correct),
            ])
            ->limit(10)
            ->get();

        return Inertia::render('clients/Show', [
            'client' => [
                'id' => $client->id,
                'reference_code' => $client->reference_code,
                'age_group' => $client->age_group->label(),
                'language' => $client->language->label(),
                'diagnosis' => $client->diagnosis,
                'notes' => $client->notes,
                'is_archived' => $client->isArchived(),
            ],
            'activities' => $assignedActivities->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'title' => $activity->title,
                'game_type' => $activity->game_type->value,
                'game_type_label' => $activity->game_type->label(),
                'language' => $activity->language->label(),
                'items_count' => (int) $activity->getAttribute('items_count'),
            ]),
            'unassignedActivities' => $unassignedActivities->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'title' => $activity->title,
                'game_type_label' => $activity->game_type->label(),
                'language' => $activity->language->label(),
            ]),
            'sessions' => $sessions->map(function (PlaySession $session): array {
                $total = (int) $session->getAttribute('trials_count');
                $correct = (int) $session->getAttribute('correct_count');

                return [
                    'id' => $session->id,
                    'activity_title' => $session->activity->title,
                    'started_at' => $session->started_at->toIso8601String(),
                    'date' => $session->started_at->toDateString(),
                    'is_finished' => $session->isFinished(),
                    'clinical_log_form_id' => $session->clinicalLogEntry?->clinical_log_form_id,
                    'total' => $total,
                    'accuracy' => $total > 0 ? round($correct / $total * 100, 1) : 0.0,
                ];
            }),
            'progress' => $progress->summarize($client),
            'goals' => $goals->summarize($client),
            'goalOptions' => [
                'gameTypes' => GameType::options(),
                'languages' => Language::options(),
                'wordPositions' => WordPosition::options(),
            ],
        ]);
    }

    /**
     * Show the page for editing a client.
     */
    public function edit(Client $client): Response
    {
        Gate::authorize('update', $client);

        return Inertia::render('clients/Edit', [
            'options' => $this->options(),
            'client' => [
                'id' => $client->id,
                'reference_code' => $client->reference_code,
                'age_group' => $client->age_group->value,
                'language' => $client->language->value,
                'diagnosis' => $client->diagnosis ?? '',
                'notes' => $client->notes ?? '',
            ],
        ]);
    }

    /**
     * Update a client.
     */
    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $client->update($request->clientAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client updated.')]);

        return to_route('clients.show', $client);
    }

    /**
     * Delete a client with all of their sessions and answers.
     */
    public function destroy(Client $client): RedirectResponse
    {
        Gate::authorize('delete', $client);

        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client deleted.')]);

        return to_route('clients.index');
    }

    /**
     * Get the choices offered on the client form.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function options(): array
    {
        return [
            'ageGroups' => AgeGroup::options(),
            'languages' => Language::options(),
        ];
    }
}
