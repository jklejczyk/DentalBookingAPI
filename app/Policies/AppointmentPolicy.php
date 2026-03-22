<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isDentist()) {
            return $user->dentist->id === $appointment->dentist_id;
        }

        return true;
    }

    public function changeStatus(User $user, Appointment $appointment): bool
    {
        if ($user->isDentist()) {
            return $user->dentist->id === $appointment->dentist_id;
        }

        return true;
    }
}
