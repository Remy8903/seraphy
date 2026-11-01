<?php

namespace App\Policies;

use App\Models\ClinicalLogForm;
use App\Models\User;

class ClinicalLogFormPolicy
{
    /**
     * Determine whether the user can view the form.
     */
    public function view(User $user, ClinicalLogForm $clinicalLogForm): bool
    {
        return $user->id === $clinicalLogForm->user_id;
    }

    /**
     * Determine whether the user can update the form.
     */
    public function update(User $user, ClinicalLogForm $clinicalLogForm): bool
    {
        return $user->id === $clinicalLogForm->user_id;
    }

    /**
     * Determine whether the user can delete the form.
     */
    public function delete(User $user, ClinicalLogForm $clinicalLogForm): bool
    {
        return $user->id === $clinicalLogForm->user_id;
    }
}
