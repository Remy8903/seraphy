<?php

namespace App\Services\Therapy;

use App\Enums\Therapy\GameType;
use App\Enums\Therapy\TrialResult;
use App\Enums\Therapy\WordPosition;
use App\Models\ActivityDirection;
use App\Models\ActivityItem;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\Trial;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientProgress
{
    /**
     * Summarize a client's accuracy per activity session, per target sound, per word group
     * (sort it) and per number of steps (follow directions).
     *
     * Only finished sessions count, optionally only those started within the given
     * dates (inclusive). Accuracy is the share of answers that were correct without a prompt.
     *
     * @return array{
     *     activities: list<array{activity_id: int, title: string, game_type: string, sessions: list<array{id: int, date: string, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>}>,
     *     sounds: list<array{target_sound: string, word_position: string|null, word_position_label: string|null, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>,
     *     categories: list<array{category: string, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>,
     *     steps: list<array{steps: int, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>,
     * }
     */
    public function summarize(Client $client, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        return [
            'activities' => $this->activities($client, $from, $to),
            'sounds' => $this->sounds($client, $from, $to),
            'categories' => $this->categories($client, $from, $to),
            'steps' => $this->steps($client, $from, $to),
        ];
    }

    /**
     * Summarize one session's answers overall and per word, story, word group, direction and try.
     *
     * For each word, `confusions` lists the wrong pictures the child chose instead (Find it!);
     * for each word group, the wrong groups the child sorted into (sort it).
     *
     * @return array{
     *     correct: int, prompted: int, incorrect: int, total: int, accuracy: float,
     *     words: list<array{id: int, word: string, emoji: string|null, image_url: string|null, correct: int, prompted: int, incorrect: int, confusions: list<array{id: int, word: string, emoji: string|null, image_url: string|null, count: int}>}>,
     *     stories: list<array{group_number: int, result: string, attempts: int, cards: list<array{id: int, word: string, emoji: string|null, image_url: string|null}>}>,
     *     categories: list<array{category: string, correct: int, prompted: int, incorrect: int, total: int, accuracy: float, confusions: list<array{category: string, count: int}>}>,
     *     directions: list<array{text: string, steps: int, result: string, expected: list<array{id: int, word: string, emoji: string|null, image_url: string|null}>, touched: list<array{id: int, word: string, emoji: string|null, image_url: string|null}>}>,
     *     steps: list<array{steps: int, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>,
     *     tries: list<array{result: string, held_ms: int, average_level: float|null}>,
     * }
     */
    public function summarizeSession(PlaySession $playSession): array
    {
        $playSession->loadMissing(['trials.contentItem', 'trials.activityDirection', 'activity.items.contentItem']);
        $gameType = $playSession->activity->game_type;

        $wordTrials = $playSession->trials->whereNotNull('content_item_id');
        $chosenPictures = ContentItem::query()
            ->whereKey($wordTrials->pluck('meta.chosen_item_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        $words = $wordTrials
            ->groupBy('content_item_id')
            ->map(function (Collection $trials) use ($chosenPictures): array {
                /** @var Trial $first */
                $first = $trials->first();

                return [
                    ...$first->contentItem->toPicture(),
                    ...$this->tally($trials->countBy(fn (Trial $trial): string => $trial->result->value)),
                    'confusions' => $trials
                        ->countBy(fn (Trial $trial): int => (int) ($trial->meta['chosen_item_id'] ?? 0))
                        ->except([0])
                        ->filter(fn (int $count, int $id): bool => $chosenPictures->has($id))
                        ->map(fn (int $count, int $id): array => [...$chosenPictures->get($id)->toPicture(), 'count' => $count])
                        ->sortByDesc('count')
                        ->values()
                        ->all(),
                ];
            })
            ->all();

        $storyCards = $playSession->activity->items
            ->whereNotNull('group_number')
            ->groupBy('group_number')
            ->map(fn (Collection $items): array => $items->map(fn (ActivityItem $item): array => $item->contentItem->toPicture())->values()->all());

        $stories = $playSession->trials
            ->whereNull('content_item_id')
            ->whereNotNull('group_number')
            ->map(fn (Trial $trial): array => [
                'group_number' => (int) $trial->group_number,
                'result' => $trial->result->value,
                'attempts' => (int) ($trial->meta['attempts'] ?? 1),
                'cards' => $storyCards->get($trial->group_number, []),
            ])
            ->values()
            ->all();

        $totals = $this->tally($playSession->trials->countBy(fn (Trial $trial): string => $trial->result->value));

        return [
            ...$totals,
            'total' => $playSession->trials->count(),
            'accuracy' => $this->accuracy($totals['correct'], $playSession->trials->count()),
            'words' => array_values($words),
            'stories' => $stories,
            'categories' => $gameType->usesCategories() ? $this->sessionCategories($playSession) : [],
            'directions' => $gameType->usesDirections() ? $this->sessionDirections($playSession) : [],
            'steps' => $gameType->usesDirections() ? $this->stepTallies($playSession->trials->whereNotNull('activity_direction_id')) : [],
            'tries' => $gameType === GameType::BalloonVoice ? $this->sessionTries($playSession) : [],
        ];
    }

    /**
     * Tally a session's sort it answers by the target word's group, with the groups chosen instead.
     *
     * @return list<array{category: string, correct: int, prompted: int, incorrect: int, total: int, accuracy: float, confusions: list<array{category: string, count: int}>}>
     */
    private function sessionCategories(PlaySession $playSession): array
    {
        $categories = $playSession->trials
            ->whereNotNull('content_item_id')
            ->groupBy(fn (Trial $trial): string => (string) $trial->contentItem->category)
            ->map(function (Collection $trials, string $category): array {
                $tally = $this->tally($trials->countBy(fn (Trial $trial): string => $trial->result->value));

                return [
                    'category' => $category,
                    ...$tally,
                    'total' => $trials->count(),
                    'accuracy' => $this->accuracy($tally['correct'], $trials->count()),
                    'confusions' => $trials
                        ->map(fn (Trial $trial): ?string => $trial->meta['chosen_category'] ?? null)
                        ->filter()
                        ->countBy()
                        ->sortDesc()
                        ->map(fn (int $count, string $chosen): array => ['category' => $chosen, 'count' => $count])
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('category')
            ->all();

        return array_values($categories);
    }

    /**
     * List a session's follow directions answers in order, with the pictures expected and touched.
     *
     * @return list<array{text: string, steps: int, result: string, expected: list<array{id: int, word: string, emoji: string|null, image_url: string|null}>, touched: list<array{id: int, word: string, emoji: string|null, image_url: string|null}>}>
     */
    private function sessionDirections(PlaySession $playSession): array
    {
        $pictures = $playSession->activity->items
            ->mapWithKeys(fn (ActivityItem $item): array => [$item->content_item_id => $item->contentItem->toPicture()]);
        $toPictures = fn (array $ids): array => array_values(array_filter(array_map(fn (int $id): ?array => $pictures->get($id), $ids)));

        return $playSession->trials
            ->filter(fn (Trial $trial): bool => $trial->activityDirection instanceof ActivityDirection)
            ->map(fn (Trial $trial): array => [
                'text' => $trial->activityDirection->text,
                'steps' => $trial->activityDirection->stepCount(),
                'result' => $trial->result->value,
                'expected' => $toPictures($trial->activityDirection->item_ids),
                'touched' => $toPictures($trial->meta['touched_item_ids'] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * List a session's balloon voice tries in order.
     *
     * @return list<array{result: string, held_ms: int, average_level: float|null}>
     */
    private function sessionTries(PlaySession $playSession): array
    {
        return $playSession->trials
            ->map(fn (Trial $trial): array => [
                'result' => $trial->result->value,
                'held_ms' => (int) ($trial->meta['held_ms'] ?? 0),
                'average_level' => isset($trial->meta['average_level']) ? (float) $trial->meta['average_level'] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Tally follow directions answers by the number of steps in each direction.
     *
     * @param  Collection<int, Trial>  $trials  answers with their direction loaded
     * @return list<array{steps: int, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>
     */
    private function stepTallies(Collection $trials): array
    {
        $steps = $trials
            ->filter(fn (Trial $trial): bool => $trial->activityDirection instanceof ActivityDirection)
            ->groupBy(fn (Trial $trial): int => $trial->activityDirection->stepCount())
            ->map(function (Collection $group, int $steps): array {
                $tally = $this->tally($group->countBy(fn (Trial $trial): string => $trial->result->value));

                return [
                    'steps' => $steps,
                    ...$tally,
                    'total' => $group->count(),
                    'accuracy' => $this->accuracy($tally['correct'], $group->count()),
                ];
            })
            ->sortKeys()
            ->all();

        return array_values($steps);
    }

    /**
     * Get the finished sessions of each activity, oldest first.
     *
     * @return list<array{activity_id: int, title: string, game_type: string, sessions: list<array{id: int, date: string, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>}>
     */
    private function activities(Client $client, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $sessions = $client->playSessions()
            ->reorder()
            ->whereNotNull('ended_at')
            ->when($from, fn ($query, CarbonInterface $from) => $query->where('started_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($query, CarbonInterface $to) => $query->where('started_at', '<=', $to->copy()->endOfDay()))
            ->with('activity:id,title,game_type')
            ->withCount([
                'trials as correct_count' => fn ($query) => $query->where('result', TrialResult::Correct),
                'trials as prompted_count' => fn ($query) => $query->where('result', TrialResult::Prompted),
                'trials as incorrect_count' => fn ($query) => $query->where('result', TrialResult::Incorrect),
            ])
            ->orderBy('started_at')
            ->orderBy('id')
            ->get();

        $activities = $sessions
            ->groupBy('activity_id')
            ->map(function (Collection $activitySessions): array {
                /** @var PlaySession $first */
                $first = $activitySessions->first();

                return [
                    'activity_id' => $first->activity_id,
                    'title' => $first->activity->title,
                    'game_type' => $first->activity->game_type->value,
                    'sessions' => array_values($activitySessions->map(function (PlaySession $session): array {
                        $correct = (int) $session->getAttribute('correct_count');
                        $prompted = (int) $session->getAttribute('prompted_count');
                        $incorrect = (int) $session->getAttribute('incorrect_count');
                        $total = $correct + $prompted + $incorrect;

                        return [
                            'id' => $session->id,
                            'date' => $session->started_at->toDateString(),
                            'correct' => $correct,
                            'prompted' => $prompted,
                            'incorrect' => $incorrect,
                            'total' => $total,
                            'accuracy' => $this->accuracy($correct, $total),
                        ];
                    })->all()),
                ];
            })
            ->all();

        return array_values($activities);
    }

    /**
     * Get accuracy per target sound and word position across all finished sessions.
     *
     * @return list<array{target_sound: string, word_position: string|null, word_position_label: string|null, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>
     */
    private function sounds(Client $client, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $rows = DB::table('trials')
            ->join('play_sessions', 'play_sessions.id', '=', 'trials.play_session_id')
            ->join('content_items', 'content_items.id', '=', 'trials.content_item_id')
            ->where('play_sessions.client_id', $client->id)
            ->whereNotNull('play_sessions.ended_at')
            ->when($from, fn ($query, CarbonInterface $from) => $query->where('play_sessions.started_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($query, CarbonInterface $to) => $query->where('play_sessions.started_at', '<=', $to->copy()->endOfDay()))
            ->whereNotNull('content_items.target_sound')
            ->groupBy('content_items.target_sound', 'content_items.word_position', 'trials.result')
            ->select('content_items.target_sound', 'content_items.word_position', 'trials.result', DB::raw('count(*) as answers'))
            ->get();

        $sounds = $rows
            ->groupBy(fn (object $row): string => $row->target_sound.'|'.$row->word_position)
            ->map(function (Collection $group): array {
                $first = $group->first();
                $tally = $this->tally($group->mapWithKeys(fn (object $row): array => [$row->result => (int) $row->answers]));
                $total = array_sum($tally);
                $position = WordPosition::tryFrom((string) $first->word_position);

                return [
                    'target_sound' => (string) $first->target_sound,
                    'word_position' => $position?->value,
                    'word_position_label' => $position?->label(),
                    ...$tally,
                    'total' => $total,
                    'accuracy' => $this->accuracy($tally['correct'], $total),
                ];
            })
            ->sortBy(['target_sound', 'word_position'])
            ->all();

        return array_values($sounds);
    }

    /**
     * Get sort it accuracy per word group across finished sessions.
     *
     * @return list<array{category: string, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>
     */
    private function categories(Client $client, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $rows = $this->finishedTrials($client, $from, $to)
            ->join('activities', 'activities.id', '=', 'play_sessions.activity_id')
            ->join('content_items', 'content_items.id', '=', 'trials.content_item_id')
            ->where('activities.game_type', GameType::SortIt->value)
            ->whereNotNull('content_items.category')
            ->groupBy('content_items.category', 'trials.result')
            ->select('content_items.category', 'trials.result', DB::raw('count(*) as answers'))
            ->get();

        $categories = $rows
            ->groupBy('category')
            ->map(function (Collection $group, string $category): array {
                $tally = $this->tally($group->mapWithKeys(fn (object $row): array => [$row->result => (int) $row->answers]));
                $total = array_sum($tally);

                return [
                    'category' => $category,
                    ...$tally,
                    'total' => $total,
                    'accuracy' => $this->accuracy($tally['correct'], $total),
                ];
            })
            ->sortBy('category')
            ->all();

        return array_values($categories);
    }

    /**
     * Get follow directions accuracy per number of steps across finished sessions.
     *
     * @return list<array{steps: int, correct: int, prompted: int, incorrect: int, total: int, accuracy: float}>
     */
    private function steps(Client $client, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $trialIds = $this->finishedTrials($client, $from, $to)
            ->whereNotNull('trials.activity_direction_id')
            ->pluck('trials.id');

        return $this->stepTallies(Trial::query()->whereKey($trialIds)->with('activityDirection')->get());
    }

    /**
     * Start a query over the answers in a client's finished sessions, optionally within dates.
     */
    private function finishedTrials(Client $client, ?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return DB::table('trials')
            ->join('play_sessions', 'play_sessions.id', '=', 'trials.play_session_id')
            ->where('play_sessions.client_id', $client->id)
            ->whereNotNull('play_sessions.ended_at')
            ->when($from, fn ($query, CarbonInterface $from) => $query->where('play_sessions.started_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($query, CarbonInterface $to) => $query->where('play_sessions.started_at', '<=', $to->copy()->endOfDay()));
    }

    /**
     * Turn answer counts keyed by result into a full tally.
     *
     * @param  Collection<string, int>  $counts
     * @return array{correct: int, prompted: int, incorrect: int}
     */
    private function tally(Collection $counts): array
    {
        return [
            'correct' => (int) $counts->get(TrialResult::Correct->value, 0),
            'prompted' => (int) $counts->get(TrialResult::Prompted->value, 0),
            'incorrect' => (int) $counts->get(TrialResult::Incorrect->value, 0),
        ];
    }

    /**
     * Get the percentage of answers that were correct, to one decimal place.
     */
    private function accuracy(int $correct, int $total): float
    {
        return $total > 0 ? round($correct / $total * 100, 1) : 0.0;
    }
}
