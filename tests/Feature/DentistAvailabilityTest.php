<?php

use App\Http\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $user = User::factory()->create();
    $this->token = $user->createToken('api test')->plainTextToken;
    $this->dentist = Dentist::factory()->create();
    $this->patient = Patient::factory()->create();
    $this->appointmentType = AppointmentType::factory()->create(['duration_minutes' => 30]);
});

it('zwraca wszystkie sloty gdy dentysta nie ma wizyt', function () {
    $date = Carbon::parse('next monday')->format('Y-m-d');

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
            'date' => $date,
            'appointment_type_id' => $this->appointmentType->id,
        ]));

    $response->assertStatus(200);

    // 8:00-18:00 = 10h = 600min / 30min = 20 slotów
    expect($response->json('data'))->toHaveCount(20);
    expect($response->json('data.0.start'))->toBe($date.' 08:00');
    expect($response->json('data.19.start'))->toBe($date.' 17:30');
});

it('nie zwraca slotów w weekend', function () {
    $saturday = Carbon::parse('next saturday')->format('Y-m-d');

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
            'date' => $saturday,
            'appointment_type_id' => $this->appointmentType->id,
        ]));

    $response->assertStatus(200);
    expect($response->json('data'))->toBeEmpty();
});

it('nie zwraca slotów zajętych przez wizytę', function () {
    $date = Carbon::parse('next monday');

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => $date->copy()->setTime(10, 0),
        'end' => $date->copy()->setTime(10, 30),
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
            'date' => $date->format('Y-m-d'),
            'appointment_type_id' => $this->appointmentType->id,
        ]));

    $response->assertStatus(200);

    $slots = collect($response->json('data'));
    expect($slots)->toHaveCount(19);
    expect($slots->pluck('start'))->not->toContain($date->format('Y-m-d').' 10:00');
});

it('anulowana wizyta nie blokuje slotu', function () {
    $date = Carbon::parse('next monday');

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => $date->copy()->setTime(10, 0),
        'end' => $date->copy()->setTime(10, 30),
        'status' => AppointmentStatusEnum::CANCELLED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
            'date' => $date->format('Y-m-d'),
            'appointment_type_id' => $this->appointmentType->id,
        ]));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(20);
});

it('brak wolnych slotów gdy cały dzień zajęty', function () {
    $date = Carbon::parse('next monday');

    for ($hour = 8; $hour < 18; $hour++) {
        for ($min = 0; $min < 60; $min += 30) {
            Appointment::factory()->create([
                'dentist_id' => $this->dentist->id,
                'patient_id' => $this->patient->id,
                'appointment_type_id' => $this->appointmentType->id,
                'start' => $date->copy()->setTime($hour, $min),
                'end' => $date->copy()->setTime($hour, $min + 30),
                'status' => AppointmentStatusEnum::BOOKED,
            ]);
        }
    }

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
            'date' => $date->format('Y-m-d'),
            'appointment_type_id' => $this->appointmentType->id,
        ]));

    $response->assertStatus(200);
    expect($response->json('data'))->toBeEmpty();
});

it('ostatni slot kończy się przed końcem pracy', function () {
    $longType = AppointmentType::factory()->create(['duration_minutes' => 60]);
    $date = Carbon::parse('next monday');

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
            'date' => $date->format('Y-m-d'),
            'appointment_type_id' => $longType->id,
        ]));

    $response->assertStatus(200);

    $slots = collect($response->json('data'));

    expect($slots)->toHaveCount(10);
    expect($slots->last()['start'])->toBe($date->format('Y-m-d').' 17:00');
});

it('wizyta dłuższa niż slot blokuje wiele slotów', function () {
    $date = Carbon::parse('next monday');

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => $date->copy()->setTime(10, 0),
        'end' => $date->copy()->setTime(11, 0),
        'status' => AppointmentStatusEnum::CONFIRMED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
            'date' => $date->format('Y-m-d'),
            'appointment_type_id' => $this->appointmentType->id,
        ]));

    $response->assertStatus(200);

    $slots = collect($response->json('data'));

    expect($slots)->toHaveCount(18);
    expect($slots->pluck('start'))->not->toContain($date->format('Y-m-d').' 10:00');
    expect($slots->pluck('start'))->not->toContain($date->format('Y-m-d').' 10:30');
});

it('zwraca 422 bez wymaganych parametrów', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.availability', [
            'dentist' => $this->dentist,
        ]));

    $response->assertStatus(422);
});

it('zwraca 401 bez tokenu', function () {
    $date = Carbon::parse('next monday')->format('Y-m-d');

    $response = $this->getJson(route('v1.dentist.availability', [
        'dentist' => $this->dentist,
        'date' => $date,
        'appointment_type_id' => $this->appointmentType->id,
    ]));

    $response->assertStatus(401);
});
