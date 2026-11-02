<?php

namespace App\Policies;

use App\Models\PlaySession;
use App\Models\User;

class PlaySessionPolicy
{
    /**
     * Determine whether the user can view the session.
     */
    public function view(User $user, PlaySession $playSession): bool
    {
        return $user->id === $playSession->client->user_id;
    }

    /**
     * Determine whether the user can record answers or notes on the session.
     */
    public function update(User $user, PlaySession $playSession): bool
    {
        return $user->id === $playSession->client->user_id;
    }
}
