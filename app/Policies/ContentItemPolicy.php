<?php

namespace App\Policies;

use App\Models\ContentItem;
use App\Models\User;

class ContentItemPolicy
{
    /**
     * Determine whether the user can use the word in an activity.
     */
    public function view(User $user, ContentItem $contentItem): bool
    {
        return $contentItem->isStarter() || $user->id === $contentItem->user_id;
    }

    /**
     * Determine whether the user can update the word. Starter words are read-only.
     */
    public function update(User $user, ContentItem $contentItem): bool
    {
        return $user->id === $contentItem->user_id;
    }

    /**
     * Determine whether the user can delete the word. Starter words are read-only.
     */
    public function delete(User $user, ContentItem $contentItem): bool
    {
        return $user->id === $contentItem->user_id;
    }
}
