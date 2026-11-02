<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncPlaySessionsRequest;
use App\Models\Client;
use App\Models\PlaySession;
use App\Models\Trial;
use App\Services\Therapy\GoalEvaluator;
use App\Services\Therapy\TrialRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class PlaySessionSyncController extends Controller
{
    /**
     * Upload sessions that were played offline, with their answers.
     *
     * Sessions and answers carry UUIDs from the device, so sending the same
     * batch again (for example after a dropped connection) adds nothing new.
     */
    public function __invoke(SyncPlaySessionsRequest $request, GoalEvaluator $evaluator): JsonResponse
    {
        $sessions = $request->sessions();

        foreach ($sessions as $session) {
            DB::transaction(function () use ($session): void {
                $playSession = PlaySession::query()->firstWhere('client_uuid', $session['client_uuid'])
                    ?? Client::query()->findOrFail($session['client_id'])->playSessions()->create([
                        'client_uuid' => $session['client_uuid'],
                        'activity_id' => $session['activity_id'],
                        'started_at' => Date::parse($session['started_at'])->setTimezone(config('app.timezone')),
                        'ended_at' => Date::parse($session['ended_at'])->setTimezone(config('app.timezone')),
                        'notes' => $session['notes'],
                        'stickers' => $session['stickers'],
                    ]);

                $now = now();

                Trial::query()->upsert(
                    array_map(fn (array $trial): array => [
                        ...$trial,
                        'play_session_id' => $playSession->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], TrialRules::attributes($session['trials'])),
                    ['play_session_id', 'client_uuid'],
                    ['content_item_id', 'group_number', 'activity_direction_id', 'result', 'response_ms', 'meta', 'updated_at'],
                );
            });
        }

        foreach (Client::query()->findMany(array_unique(array_column($sessions, 'client_id'))) as $client) {
            $evaluator->evaluate($client);
        }

        return response()->json(['synced' => array_column($sessions, 'client_uuid')]);
    }
}
