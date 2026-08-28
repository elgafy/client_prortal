<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;

class CommentPolicy
{
    /**
     * Internal users (administrators and staff) comment on anything.
     * Client users comment only on their own client's records (PRD §24, §80.9).
     */
    public function create(User $user, Project|Payment|null $commentable = null): bool
    {
        if ($user->isInternal()) {
            return true;
        }

        if (! $user->client_id || ! $commentable) {
            return false;
        }

        return $commentable->client_id === $user->client_id;
    }

    /**
     * Authors and administrators may remove their comments.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || $comment->user_id === $user->id;
    }
}
