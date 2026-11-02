<?php

namespace App\Http\Controllers;

use App\Enums\Therapy\GoalStatus;
use App\Http\Requests\TherapyGoalRequest;
use App\Models\Client;
use App\Models\TherapyGoal;
use App\Services\Therapy\GoalEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TherapyGoalController extends Controller
{
    /**
     * Add a goal to the client and check it against the sessions played so far.
     */
    public function store(TherapyGoalRequest $request, Client $client, GoalEvaluator $evaluator): RedirectResponse
    {
        Gate::authorize('update', $client);

        $goal = $client->goals()->create($request->goalAttributes());

        $this->flashSaved($evaluator->evaluateGoal($goal), __('Goal added.'));

        return to_route('clients.show', $client);
    }

    /**
     * Update a goal. Changing the criteria of an achieved goal reopens it and checks it again.
     */
    public function update(TherapyGoalRequest $request, TherapyGoal $therapyGoal, GoalEvaluator $evaluator): RedirectResponse
    {
        Gate::authorize('update', $therapyGoal);

        $therapyGoal->fill($request->goalAttributes());

        if ($therapyGoal->status === GoalStatus::Achieved && $therapyGoal->isDirty(TherapyGoal::CRITERIA)) {
            $therapyGoal->forceFill(['status' => GoalStatus::Active, 'achieved_at' => null]);
        }

        $therapyGoal->save();

        $this->flashSaved($evaluator->evaluateGoal($therapyGoal), __('Goal updated.'));

        return to_route('clients.show', $therapyGoal->client_id);
    }

    /**
     * Delete a goal.
     */
    public function destroy(TherapyGoal $therapyGoal): RedirectResponse
    {
        Gate::authorize('delete', $therapyGoal);

        $therapyGoal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Goal deleted.')]);

        return to_route('clients.show', $therapyGoal->client_id);
    }

    /**
     * Confirm the save, mentioning when the client has already reached the goal.
     */
    private function flashSaved(bool $wasAchieved, string $message): void
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wasAchieved ? $message.' '.__('The client has already reached it.') : $message,
        ]);
    }
}
