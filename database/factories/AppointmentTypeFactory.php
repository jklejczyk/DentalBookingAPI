<?php

namespace Database\Factories;

use App\Http\Filters\V1\AppointmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentTypeFactory extends Factory
{
    protected $model = AppointmentType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(fake()->numberBetween(1, 3) ,true),
            'description' => fake()->sentence(),
            'duration_minutes' =>  fake()->randomElement(['15', '30', '45', '60', '90']),
        ];
    }
}
