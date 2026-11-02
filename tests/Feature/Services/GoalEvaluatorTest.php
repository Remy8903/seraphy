<?php

use App\Enums\Therapy\GoalStatus;
use App\Enums\Therapy\Language;
use App\Enums\Therapy\WordPosition;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ContentItem;
use App\Models\PlaySession;
use App\Models\TherapyGoal;
use App\Models\Trial;
use App\Services\Therapy\GoalEvaluator;

/**
 * Create a finished session for the client with the given number of correct and incorrect answers on one word.
 */
function finishedSessionWithAnswers(Client $client, int $correct, int $incorrect, int $daysAgo, ?ContentItem $word = null, ?Activity $activity = null): PlaySession
{
    $session = PlaySession::factory()
        ->for($client)
        ->for($activity ?? Activity::factory()->for($client->user))
        ->finished()
        ->create(['started_at' => now()->subDays($daysAgo)]);

    $word ??= ContentItem::factory()->starter()->create();

    Trial::factory()->for($session)->for($word)->correct()->count($correct)->create();
    Trial::factory()->for($session)->for($word)->incorrect()->count($incorrect)->create();

    return $session;
}

test('a goal is achieved after the required consecutive sessions reach the target', function () {
    $client = Client::factory()->create();
    $goal = TherapyGoal::factory()->for($client)->create(['target_accuracy' => 80, 'consecutive_sessions' => 3]);
    finishedSessionWithAnswers($client, 8, 2, daysAgo: 3);
    finishedSessionWithAnswers($client, 9, 1, daysAgo: 2);

    expect((new GoalEvaluator)->evaluate($client))->toBeEmpty();
    expect($goal->refresh()->status)->toBe(GoalStatus::Active);

    finishedSessionWithAnswers($client, 10, 0, daysAgo: 1);

    expect((new GoalEvaluator)->evaluate($client)->modelKeys())->toBe([$goal->id]);
    expect($goal->refresh()->status)->toBe(GoalStatus::Achieved);
    expect($goal->achieved_at)->not->toBeNull();
});

test('a goal is not achieved when one of the latest sessions is below the target', function () {
    $client = Client::factory()->create();
    $goal = TherapyGoal::factory()->for($client)->create(['target_accuracy' => 80, 'consecutive_sessions' => 3]);
    finishedSessionWithAnswers($client, 9, 1, daysAgo: 3);
    finishedSessionWithAnswers($client, 7, 3, daysAgo: 2);
    finishedSessionWithAnswers($client, 9, 1, daysAgo: 1);

    $evaluator = new GoalEvaluator;

    expect($evaluator->evaluate($client))->toBeEmpty();
    expect($evaluator->progress($goal)['streak'])->toBe(1);
    expect($goal->refresh()->status)->toBe(GoalStatus::Active);
});

test('sessions with fewer matching answers than the minimum do not count', function () {
    $client = Client::factory()->create();
    $goal = TherapyGoal::factory()->for($client)->create(['target_accuracy' => 80, 'consecutive_sessions' => 2, 'min_trials_per_session' => 10]);
    finishedSessionWithAnswers($client, 9, 1, daysAgo: 3);
    $shortSession = finishedSessionWithAnswers($client, 1, 3, daysAgo: 2);
    finishedSessionWithAnswers($client, 10, 0, daysAgo: 1);

    $progress = (new GoalEvaluator)->progress($goal);

    expect(array_column($progress['sessions'], 'play_session_id'))->not->toContain($shortSession->id);
    expect($progress['streak'])->toBe(2);
});

test('prompted answers count as not correct', function () {
    $client = Client::factory()->create();
    $goal = TherapyGoal::factory()->for($client)->create(['target_accuracy' => 50, 'consecutive_sessions' => 1]);
    $session = PlaySession::factory()->for($client)->finished()->create();
    Trial::factory()->for($session)->correct()->count(4)->create();
    Trial::factory()->for($session)->prompted()->count(6)->create();

    expect((new GoalEvaluator)->progress($goal)['sessions'][0])->toMatchArray(['trials' => 10, 'correct' => 4, 'accuracy' => 40.0, 'met' => false]);
});

test('only answers matching the goal\'s sound, position and language count', function () {
    $client = Client::factory()->create();
    $goal = TherapyGoal::factory()->for($client)->create([
        'language' => Language::Malay,
        'target_sound' => 's',
        'word_position' => WordPosition::Initial,
        'target_accuracy' => 80,
        'consecutive_sessions' => 1,
        'min_trials_per_session' => 5,
    ]);
    $initialS = ContentItem::factory()->starter()->create(['target_sound' => 's', 'word_position' => WordPosition::Initial]);
    $finalS = ContentItem::factory()->starter()->create(['target_sound' => 's', 'word_position' => WordPosition::Final]);
    $initialK = ContentItem::factory()->starter()->create(['target_sound' => 'k', 'word_position' => WordPosition::Initial]);
    $malayActivity = Activity::factory()->for($client->user)->create(['language' => Language::Malay]);
    $englishActivity = Activity::factory()->for($client->user)->create(['language' => Language::English]);

    finishedSessionWithAnswers($client, 0, 10, daysAgo: 2, word: $initialS, activity: $englishActivity);
    $session = finishedSessionWithAnswers($client, 5, 0, daysAgo: 1, word: $initialS, activity: $malayActivity);
    Trial::factory()->for($session)->for($finalS)->incorrect()->count(5)->create();
    Trial::factory()->for($session)->for($initialK)->incorrect()->count(5)->create();

    $progress = (new GoalEvaluator)->progress($goal);

    expect($progress['sessions'])->toHaveCount(1);
    expect($progress['sessions'][0])->toMatchArray(['play_session_id' => $session->id, 'trials' => 5, 'accuracy' => 100.0]);
    expect((new GoalEvaluator)->evaluate($client))->toHaveCount(1);
});

test('unfinished sessions do not count', function () {
    $client = Client::factory()->create();
    $goal = TherapyGoal::factory()->for($client)->create(['consecutive_sessions' => 1]);
    $session = PlaySession::factory()->for($client)->create();
    Trial::factory()->for($session)->correct()->count(10)->create();

    expect((new GoalEvaluator)->progress($goal)['sessions'])->toBeEmpty();
});

test('discontinued and achieved goals are not evaluated', function () {
    $client = Client::factory()->create();
    $discontinued = TherapyGoal::factory()->for($client)->discontinued()->create(['consecutive_sessions' => 1]);
    $achieved = TherapyGoal::factory()->for($client)->achieved()->create(['consecutive_sessions' => 1, 'achieved_at' => now()->subMonth()]);
    finishedSessionWithAnswers($client, 10, 0, daysAgo: 1);

    expect((new GoalEvaluator)->evaluate($client))->toBeEmpty();
    expect($discontinued->refresh()->status)->toBe(GoalStatus::Discontinued);
    expect($achieved->refresh()->achieved_at->isSameDay(now()->subMonth()))->toBeTrue();
});

test('a session summary lists the goals it counted toward', function () {
    $client = Client::factory()->create();
    $word = ContentItem::factory()->starter()->create(['target_sound' => 's']);
    $counted = TherapyGoal::factory()->for($client)->create(['target_sound' => 's', 'target_accuracy' => 80, 'min_trials_per_session' => 10]);
    TherapyGoal::factory()->for($client)->create(['target_sound' => 'k']);
    TherapyGoal::factory()->for($client)->discontinued()->create();
    $session = finishedSessionWithAnswers($client, 9, 1, daysAgo: 1, word: $word);

    $goals = (new GoalEvaluator)->goalsCountedBy($session);

    expect($goals)->toHaveCount(1);
    expect($goals[0])->toMatchArray(['id' => $counted->id, 'accuracy' => 90.0, 'met' => true, 'streak' => 1]);
});
