<?php

namespace App\Policies;

use App\Models\TherapyGoal;
use App\Models\User;

class TherapyGoalPolicy
{
    /**
     * Determine whether the user can update the goal.
     */
    public function update(User $user, TherapyGoal $therapyGoal): bool
    {
        return $user->id === $therapyGoal->client->user_id;
    }

    /**
     * Determine whether the user can delete the goal.
     */
    public function delete(User $user, TherapyGoal $therapyGoal): bool
    {
        return $user->id === $therapyGoal->client->user_id;
    }
}
