<?php

namespace App\Http\Enums;

enum GenderEnum: string
{
    case MALE = 'M';
    case FEMALE = 'F';

    public function description(): string
    {
        return match ($this) {
            self::MALE => 'Mężczyzna',
            self::FEMALE => 'Kobieta',
        };
    }
}
