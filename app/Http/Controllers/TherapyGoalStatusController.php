<?php

namespace App\Http\Controllers;

use App\Enums\Therapy\GoalStatus;
use App\Models\TherapyGoal;
use App\Services\Therapy\GoalEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TherapyGoalStatusController extends Controller
{
    /**
     * Discontinue an active goal, or resume a discontinued one.
     */
    public function __invoke(TherapyGoal $therapyGoal, GoalEvaluator $evaluator): RedirectResponse
    {
        Gate::authorize('update', $therapyGoal);

        abort_if($therapyGoal->status === GoalStatus::Achieved, 409, 'An achieved goal cannot be discontinued.');

        $wasDiscontinued = $therapyGoal->status === GoalStatus::Discontinued;

        $therapyGoal->forceFill([
            'status' => $wasDiscontinued ? GoalStatus::Active : GoalStatus::Discontinued,
        ])->save();

        $message = match (true) {
            ! $wasDiscontinued => __('Goal discontinued.'),
            $evaluator->evaluateGoal($therapyGoal) => __('Goal resumed. The client has already reached it.'),
            default => __('Goal resumed.'),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('clients.show', $therapyGoal->client_id);
    }
}
