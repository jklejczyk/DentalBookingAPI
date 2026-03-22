<?php

namespace App\Http\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case RECEPTIONIST = 'receptionist';
    case DENTIST = 'dentist';
}