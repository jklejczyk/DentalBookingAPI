<?php

namespace App\Http\Enums;

enum AppointmentStatusEnum: string
{
    case BOOKED = 'booked';
    case CONFIRMED = 'confirmed';
    case FINISHED = 'finished';

    public function description(): string
    {
        return match ($this) {
            self::BOOKED => 'Booked',
            self::CONFIRMED => 'Confirmed',
            self::FINISHED => 'Finished',
        };
    }
}
