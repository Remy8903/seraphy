<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }
}
