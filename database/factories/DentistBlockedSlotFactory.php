<?php

namespace Database\Factories;

use App\Models\Dentist;
use App\Models\DentistBlockedSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DentistBlockedSlot>
 */
class DentistBlockedSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dentist = Dentist::inRandomOrder()->first();
        $start = Carbon::instance($this->faker->dateTimeBetween('-1 month', '+1 month'))
            ->setMinute($this->faker->randomElement([0, 15, 30, 45]));

        return [
            'dentist_id' => $dentist->id,
            'reason' => $this->faker->optional()->sentence(),
            'start' => $start,
            'end' => $start->copy()->addMinutes($this->faker->randomElement([30, 60, 120])),
        ];
    }
}
