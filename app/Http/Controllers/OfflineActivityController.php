<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Client;
use App\Services\Therapy\GameContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class OfflineActivityController extends Controller
{
    /**
     * Get an assigned activity with everything needed to play it offline with the client.
     *
     * Only the client's reference code is included, the same as on the play screen.
     */
    public function __invoke(Client $client, Activity $activity, GameContent $content): JsonResponse
    {
        Gate::authorize('view', $client);

        abort_unless($client->activities()->whereKey($activity->id)->exists(), 404);

        return response()->json([
            'client' => [
                'id' => $client->id,
                'reference_code' => $client->reference_code,
            ],
            ...$content->forActivity($activity),
            'downloaded_at' => now()->toIso8601String(),
        ]);
    }
}
