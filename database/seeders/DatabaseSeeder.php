<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\DentistBlockedSlot;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        AppointmentType::factory(10)->create();
        Patient::factory(10)->create();
        Dentist::factory(10)->create();
        Appointment::factory(10)->create();
        DentistBlockedSlot::factory(10)->create();
    }
}
