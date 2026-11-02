<?php

namespace App\Http\Controllers;

use App\Enums\Therapy\GoalStatus;
use App\Enums\Therapy\TrialResult;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\TherapyGoal;
use App\Models\Trial;
use App\Models\User;
use App\Services\ClinicalHoursSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The number of weeks shown in the sessions chart, including this one.
     */
    private const int CHART_WEEKS = 8;

    /**
     * Show an overview of the clinician's clinical hours, clients, activities and sessions.
     */
    public function __invoke(Request $request, ClinicalHoursSummary $hoursSummary): Response
    {
        $user = $request->user();
        $clientIds = $user->clients()->pluck('id');

        $forms = $user->clinicalLogForms()->withSum('entries', 'duration_minutes')->get();

        return Inertia::render('Dashboard', [
            'hours' => [
                'categories' => $hoursSummary->summarize($forms)['categories'],
                'forms_count' => $forms->count(),
            ],
            'counts' => [
                'active_clients' => $user->clients()->active()->count(),
                'activities' => $user->activities()->count(),
                'words' => ContentItem::query()->visibleTo($user)->count(),
                'own_words' => $user->contentItems()->count(),
            ],
            'sessions' => $this->sessionStats($clientIds),
            'weeklySessions' => $this->weeklySessions($clientIds),
            'recentSessions' => $this->recentSessions($clientIds),
            'goals' => $this->goals($user),
        ]);
    }

    /**
     * Count finished sessions this week and the share of correct answers over the last 30 days.
     *
     * @param  Collection<int, int>  $clientIds
     * @return array{this_week: int, total: int, answers_last_30_days: int, accuracy_last_30_days: float|null}
     */
    private function sessionStats(Collection $clientIds): array
    {
        $answers = Trial::query()
            ->whereHas('playSession', fn (Builder $query) => $this->finishedSessions($query, $clientIds)
                ->where('ended_at', '>=', now()->subDays(30)))
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when result = ? then 1 else 0 end) as correct', [TrialResult::Correct->value])
            ->first();

        $total = (int) $answers->total;

        return [
            'this_week' => $this->finishedSessions(PlaySession::query(), $clientIds)
                ->where('ended_at', '>=', now()->startOfWeek())
                ->count(),
            'total' => $this->finishedSessions(PlaySession::query(), $clientIds)->count(),
            'answers_last_30_days' => $total,
            'accuracy_last_30_days' => $total > 0 ? round((int) $answers->correct / $total * 100, 1) : null,
        ];
    }

    /**
     * Count finished sessions per week, oldest week first, including weeks with none.
     *
     * @param  Collection<int, int>  $clientIds
     * @return list<array{week_start: string, sessions: int}>
     */
    private function weeklySessions(Collection $clientIds): array
    {
        $firstWeek = now()->startOfWeek()->subWeeks(self::CHART_WEEKS - 1);

        $sessionsPerWeek = $this->finishedSessions(PlaySession::query(), $clientIds)
            ->where('ended_at', '>=', $firstWeek)
            ->pluck('ended_at')
            ->countBy(fn ($endedAt): string => $endedAt->startOfWeek()->toDateString());

        return array_map(function (int $offset) use ($firstWeek, $sessionsPerWeek): array {
            $weekStart = $firstWeek->copy()->addWeeks($offset)->toDateString();

            return ['week_start' => $weekStart, 'sessions' => $sessionsPerWeek->get($weekStart, 0)];
        }, range(0, self::CHART_WEEKS - 1));
    }

    /**
     * Get the latest finished sessions with their accuracy.
     *
     * @param  Collection<int, int>  $clientIds
     * @return list<array{id: int, client_reference: string, activity_title: string, game_type_label: string, ended_at: string, answers: int, accuracy: float}>
     */
    private function recentSessions(Collection $clientIds): array
    {
        return $this->finishedSessions(PlaySession::query(), $clientIds)
            ->with(['client:id,reference_code', 'activity:id,title,game_type'])
            ->withCount([
                'trials',
                'trials as correct_trials_count' => fn (Builder $query) => $query->where('result', TrialResult::Correct),
            ])
            ->latest('ended_at')
            ->limit(5)
            ->get()
            ->map(fn (PlaySession $session): array => [
                'id' => $session->id,
                'client_reference' => $session->client->reference_code,
                'activity_title' => $session->activity->title,
                'game_type_label' => $session->activity->game_type->label(),
                'ended_at' => $session->ended_at->toIso8601String(),
                'answers' => (int) $session->getAttribute('trials_count'),
                'accuracy' => $session->getAttribute('trials_count') > 0
                    ? round($session->getAttribute('correct_trials_count') / $session->getAttribute('trials_count') * 100, 1)
                    : 0.0,
            ])
            ->all();
    }

    /**
     * Get the goals being worked on for active clients, and how many have been achieved.
     *
     * @return array{active_count: int, achieved_count: int, active: list<array{id: int, client_id: int, client_reference: string, description: string, target_accuracy: int}>}
     */
    private function goals(User $user): array
    {
        $goals = TherapyGoal::query()->whereHas('client', fn (Builder $query) => $query
            ->whereBelongsTo($user)
            ->active());

        return [
            'active_count' => (clone $goals)->where('status', GoalStatus::Active)->count(),
            'achieved_count' => (clone $goals)->where('status', GoalStatus::Achieved)->count(),
            'active' => (clone $goals)
                ->where('status', GoalStatus::Active)
                ->with('client:id,reference_code')
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (TherapyGoal $goal): array => [
                    'id' => $goal->id,
                    'client_id' => $goal->client_id,
                    'client_reference' => $goal->client->reference_code,
                    'description' => $goal->description,
                    'target_accuracy' => $goal->target_accuracy,
                ])
                ->all(),
        ];
    }

    /**
     * Limit a session query to finished sessions of the clinician's clients.
     *
     * @template TQuery of Builder
     *
     * @param  TQuery  $query
     * @param  Collection<int, int>  $clientIds
     * @return TQuery
     */
    private function finishedSessions(Builder $query, Collection $clientIds): Builder
    {
        return $query->whereIn('client_id', $clientIds)->whereNotNull('ended_at');
    }
}
