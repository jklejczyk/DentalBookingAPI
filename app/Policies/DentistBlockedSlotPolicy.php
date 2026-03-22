<?php

namespace App\Policies;

use App\Models\DentistBlockedSlot;
use App\Models\User;

class DentistBlockedSlotPolicy
{
    public function viewAny(User $user, int $dentistId): bool
    {
        if ($user->isDentist()) {
            return $user->dentist->id === $dentistId;
        }

        return true;
    }

    public function create(User $user, int $dentistId): bool
    {
        if ($user->isDentist()) {
            return $user->dentist->id === $dentistId;
        }

        return true;
    }

    public function delete(User $user, DentistBlockedSlot $blockedSlot): bool
    {
        if ($user->isDentist()) {
            return $user->dentist->id === $blockedSlot->dentist_id;
        }

        return true;
    }
}
