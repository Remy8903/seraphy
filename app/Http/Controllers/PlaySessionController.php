<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaySessionRequest;
use App\Http\Requests\UpdatePlaySessionRequest;
use App\Models\Client;
use App\Models\PlaySession;
use App\Services\Therapy\ClientProgress;
use App\Services\Therapy\GameContent;
use App\Services\Therapy\GoalEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlaySessionController extends Controller
{
    /**
     * Start a game session with a client.
     */
    public function store(StorePlaySessionRequest $request): RedirectResponse
    {
        $client = Client::query()->findOrFail($request->integer('client_id'));

        $playSession = $client->playSessions()->create([
            'activity_id' => $request->integer('activity_id'),
            'started_at' => now(),
        ]);

        return to_route('play-sessions.show', $playSession);
    }

    /**
     * Show the game screen.
     */
    public function show(PlaySession $playSession, GameContent $content): Response|RedirectResponse
    {
        Gate::authorize('view', $playSession);

        if ($playSession->isFinished()) {
            return to_route('play-sessions.summary', $playSession);
        }

        return Inertia::render('play/Show', [
            'session' => [
                'id' => $playSession->id,
                'client_id' => $playSession->client_id,
                'recorded_trials' => $playSession->trials()->count(),
                'stickers' => $playSession->stickers ?? [],
            ],
            ...$content->forActivity($playSession->activity),
        ]);
    }

    /**
     * Save the clinician's notes on a session.
     */
    public function update(UpdatePlaySessionRequest $request, PlaySession $playSession): RedirectResponse
    {
        Gate::authorize('update', $playSession);

        $playSession->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notes saved.')]);

        return to_route('play-sessions.summary', $playSession);
    }

    /**
     * End a session and update the client's goals. A session without any answers is discarded.
     */
    public function finish(PlaySession $playSession, GoalEvaluator $evaluator): RedirectResponse
    {
        Gate::authorize('update', $playSession);

        if (! $playSession->trials()->exists()) {
            $playSession->delete();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Session discarded because no answers were recorded.')]);

            return to_route('clients.show', $playSession->client_id);
        }

        if (! $playSession->isFinished()) {
            $playSession->update(['ended_at' => now()]);

            $achievedGoals = $evaluator->evaluate($playSession->client);

            if ($achievedGoals->isNotEmpty()) {
                Inertia::flash('achievedGoals', $achievedGoals->pluck('description')->all());
            }
        }

        return to_route('play-sessions.summary', $playSession);
    }

    /**
     * Show the results of a finished session.
     */
    public function summary(PlaySession $playSession, ClientProgress $progress, GoalEvaluator $goals): Response|RedirectResponse
    {
        Gate::authorize('view', $playSession);

        if (! $playSession->isFinished()) {
            return to_route('play-sessions.show', $playSession);
        }

        $playSession->load(['client', 'activity', 'clinicalLogEntry:id,clinical_log_form_id']);

        return Inertia::render('play/Summary', [
            'session' => [
                'id' => $playSession->id,
                'started_at' => $playSession->started_at->toIso8601String(),
                'date' => $playSession->started_at->toDateString(),
                'clinical_log_form_id' => $playSession->clinicalLogEntry?->clinical_log_form_id,
                'duration_minutes' => max(1, (int) round($playSession->started_at->diffInMinutes($playSession->ended_at))),
                'notes' => $playSession->notes ?? '',
                'stickers' => $playSession->stickers ?? [],
            ],
            'client' => [
                'id' => $playSession->client->id,
                'reference_code' => $playSession->client->reference_code,
            ],
            'activity' => [
                'title' => $playSession->activity->title,
                'game_type_label' => $playSession->activity->game_type->label(),
            ],
            'summary' => $progress->summarizeSession($playSession),
            'goals' => $goals->goalsCountedBy($playSession),
        ]);
    }
}
