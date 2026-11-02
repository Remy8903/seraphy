<?php

namespace App\Services\Therapy;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\GoalStatus;
use App\Enums\Therapy\Language;
use App\Enums\Therapy\TrialResult;
use App\Enums\Therapy\WordPosition;
use App\Models\Client;
use App\Models\PlaySession;
use App\Models\TherapyGoal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GoalEvaluator
{
    /**
     * Mark every active goal of the client that has now been reached as achieved.
     *
     * @return Collection<int, TherapyGoal> the goals achieved by this evaluation
     */
    public function evaluate(Client $client): Collection
    {
        return $client->goals()
            ->where('status', GoalStatus::Active)
            ->get()
            ->filter(fn (TherapyGoal $goal): bool => $this->evaluateGoal($goal))
            ->values();
    }

    /**
     * Mark the goal as achieved if it is active and has been reached.
     *
     * Returns whether the goal was achieved by this call.
     */
    public function evaluateGoal(TherapyGoal $goal): bool
    {
        if (! $goal->isActive() || $this->progress($goal)['streak'] < $goal->consecutive_sessions) {
            return false;
        }

        $goal->markAchieved();

        return true;
    }

    /**
     * Get the client's finished sessions that count toward the goal, oldest first,
     * and how many of the latest ones in a row reached the target accuracy.
     *
     * A session counts when it has at least `min_trials_per_session` answers that
     * match the goal's filters. Prompted answers count as not correct.
     *
     * @return array{sessions: list<array{play_session_id: int, date: string, trials: int, correct: int, accuracy: float, met: bool}>, streak: int}
     */
    public function progress(TherapyGoal $goal): array
    {
        $sessions = $this->matchingTrials($goal)
            ->groupBy('play_sessions.id', 'play_sessions.started_at')
            ->havingRaw('count(*) >= ?', [$goal->min_trials_per_session])
            ->orderBy('play_sessions.started_at')
            ->orderBy('play_sessions.id')
            ->select(['play_sessions.id as play_session_id', 'play_sessions.started_at'])
            ->selectRaw('count(*) as trials')
            ->selectRaw('sum(case when trials.result = ? then 1 else 0 end) as correct', [TrialResult::Correct->value])
            ->get()
            ->map(function (object $row) use ($goal): array {
                $trials = (int) $row->trials;
                $correct = (int) $row->correct;

                return [
                    'play_session_id' => (int) $row->play_session_id,
                    'date' => Carbon::parse($row->started_at)->toDateString(),
                    'trials' => $trials,
                    'correct' => $correct,
                    'accuracy' => round($correct / $trials * 100, 1),
                    'met' => $correct * 100 >= $goal->target_accuracy * $trials,
                ];
            });

        $streak = $sessions->reverse()->takeWhile(fn (array $session): bool => $session['met'])->count();

        return [
            'sessions' => $sessions->values()->all(),
            'streak' => $streak,
        ];
    }

    /**
     * Describe each of the client's goals with its progress, for display.
     *
     * @return list<array{id: int, description: string, status: string, status_label: string, achieved_at: string|null, game_type: string|null, game_type_label: string|null, language: string|null, language_label: string|null, target_sound: string|null, word_position: string|null, word_position_label: string|null, target_accuracy: int, consecutive_sessions: int, min_trials_per_session: int, streak: int, recent_sessions: list<array{play_session_id: int, date: string, trials: int, correct: int, accuracy: float, met: bool}>}>
     */
    public function summarize(Client $client): array
    {
        return $client->goals
            ->map(function (TherapyGoal $goal): array {
                $progress = $this->progress($goal);

                return [
                    'id' => $goal->id,
                    'description' => $goal->description,
                    'status' => $goal->status->value,
                    'status_label' => $goal->status->label(),
                    'achieved_at' => $goal->achieved_at?->toDateString(),
                    'game_type' => $goal->game_type?->value,
                    'game_type_label' => $goal->game_type?->label(),
                    'language' => $goal->language?->value,
                    'language_label' => $goal->language?->label(),
                    'target_sound' => $goal->target_sound,
                    'word_position' => $goal->word_position?->value,
                    'word_position_label' => $goal->word_position?->label(),
                    'target_accuracy' => $goal->target_accuracy,
                    'consecutive_sessions' => $goal->consecutive_sessions,
                    'min_trials_per_session' => $goal->min_trials_per_session,
                    'streak' => min($progress['streak'], $goal->consecutive_sessions),
                    'recent_sessions' => array_slice($progress['sessions'], -$goal->consecutive_sessions),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Get the goals that the session counted toward, with the session's accuracy for each.
     *
     * Discontinued goals are left out.
     *
     * @return list<array{id: int, description: string, status: string, accuracy: float, met: bool, target_accuracy: int, streak: int, consecutive_sessions: int}>
     */
    public function goalsCountedBy(PlaySession $playSession): array
    {
        return $playSession->client->goals
            ->reject(fn (TherapyGoal $goal): bool => $goal->status === GoalStatus::Discontinued)
            ->map(function (TherapyGoal $goal) use ($playSession): ?array {
                $progress = $this->progress($goal);
                $session = collect($progress['sessions'])->firstWhere('play_session_id', $playSession->id);

                if ($session === null) {
                    return null;
                }

                return [
                    'id' => $goal->id,
                    'description' => $goal->description,
                    'status' => $goal->status->value,
                    'accuracy' => $session['accuracy'],
                    'met' => $session['met'],
                    'target_accuracy' => $goal->target_accuracy,
                    'streak' => min($progress['streak'], $goal->consecutive_sessions),
                    'consecutive_sessions' => $goal->consecutive_sessions,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Start a query over the client's answers in finished sessions that match the goal's filters.
     */
    private function matchingTrials(TherapyGoal $goal): Builder
    {
        return DB::table('trials')
            ->join('play_sessions', 'play_sessions.id', '=', 'trials.play_session_id')
            ->join('activities', 'activities.id', '=', 'play_sessions.activity_id')
            ->leftJoin('content_items', 'content_items.id', '=', 'trials.content_item_id')
            ->where('play_sessions.client_id', $goal->client_id)
            ->whereNotNull('play_sessions.ended_at')
            ->when($goal->game_type, fn (Builder $query, GameType $gameType) => $query->where('activities.game_type', $gameType->value))
            ->when($goal->language, fn (Builder $query, Language $language) => $query->where('activities.language', $language->value))
            ->when($goal->target_sound, fn (Builder $query, string $sound) => $query->where('content_items.target_sound', $sound))
            ->when($goal->word_position, fn (Builder $query, WordPosition $position) => $query->where('content_items.word_position', $position->value));
    }
}
