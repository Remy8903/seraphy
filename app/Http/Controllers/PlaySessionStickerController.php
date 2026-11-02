<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePlaySessionStickersRequest;
use App\Models\PlaySession;
use Illuminate\Http\JsonResponse;

class PlaySessionStickerController extends Controller
{
    /**
     * Replace the stickers the child has earned in the session.
     *
     * The whole board is sent each time, so sending it again is harmless.
     */
    public function __invoke(UpdatePlaySessionStickersRequest $request, PlaySession $playSession): JsonResponse
    {
        $playSession->update(['stickers' => array_values($request->validated('stickers'))]);

        return response()->json(['stickers' => $playSession->stickers]);
    }
}
