<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Internal users (administrators and staff) manage clients.
     * Client-role users only ever see their own account through the portal.
     */
    public function viewAny(User $user): bool
    {
        return $user->isInternal();
    }

    public function view(User $user, Client $client): bool
    {
        return $user->isInternal();
    }

    public function create(User $user): bool
    {
        return $user->isInternal();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->isInternal();
    }

    public function archive(User $user, Client $client): bool
    {
        return $user->isInternal();
    }

    /**
     * Sending portal invitations is an administrative action.
     */
    public function invite(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }
}
