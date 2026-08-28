<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Internal users (administrators and staff) manage payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->isInternal();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isInternal();
    }

    public function create(User $user): bool
    {
        return $user->isInternal();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isInternal();
    }

    public function void(User $user, Payment $payment): bool
    {
        return $user->isInternal() && $payment->status === Payment::STATUS_ACTIVE;
    }
}
