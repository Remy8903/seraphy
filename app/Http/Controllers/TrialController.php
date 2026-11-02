<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrialsRequest;
use App\Models\PlaySession;
use App\Models\Trial;
use Illuminate\Http\JsonResponse;

class TrialController extends Controller
{
    /**
     * Record a batch of answers.
     *
     * Each answer carries a UUID from the device, so a batch that is sent again
     * after a dropped connection, or a corrected answer, updates the existing
     * row instead of adding a duplicate.
     */
    public function store(StoreTrialsRequest $request, PlaySession $playSession): JsonResponse
    {
        $now = now();

        $rows = array_map(fn (array $trial): array => [
            ...$trial,
            'play_session_id' => $playSession->id,
            'created_at' => $now,
            'updated_at' => $now,
        ], $request->trialAttributes());

        Trial::query()->upsert(
            $rows,
            ['play_session_id', 'client_uuid'],
            ['content_item_id', 'group_number', 'activity_direction_id', 'result', 'response_ms', 'meta', 'updated_at'],
        );

        return response()->json([
            'saved' => array_column($rows, 'client_uuid'),
            'recorded_trials' => $playSession->trials()->count(),
        ]);
    }
}
