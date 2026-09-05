<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Internal users (administrators and staff) manage projects.
     */
    public function viewAny(User $user): bool
    {
        return $user->isInternal();
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isInternal();
    }

    public function create(User $user): bool
    {
        return $user->isInternal();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isInternal();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isInternal();
    }
}
