<?php

namespace Database\Factories;

use App\Http\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appointmentType = AppointmentType::inRandomOrder()->first();
        $minuteSlot = $this->faker->randomElement([0, 15, 30, 45]);
        $startDateTime = Carbon::instance($this->faker->dateTimeBetween('-1 year'))->setMinute($minuteSlot)->setSecond(0);
        $endDateTime = $startDateTime->copy()->addMinutes($appointmentType->duration_minutes);

        return [
            'start' => $startDateTime,
            'end' => $endDateTime,
            'dentist_id' => Dentist::inRandomOrder()->first()->id,
            'patient_id' => Patient::inRandomOrder()->first()->id,
            'appointment_type_id' => $appointmentType->id,
            'status' => $this->faker->randomElement([AppointmentStatusEnum::BOOKED, AppointmentStatusEnum::CONFIRMED, AppointmentStatusEnum::COMPLETED, AppointmentStatusEnum::CANCELLED]),
        ];
    }
}
