<?php

namespace App\Http\Enums;

enum AppointmentStatusEnum: string
{
    case BOOKED = 'booked';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function description(): string
    {
        return match ($this) {
            self::BOOKED => 'Booked',
            self::CONFIRMED => 'Confirmed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
