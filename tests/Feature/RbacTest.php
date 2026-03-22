<?php

use App\Http\Enums\AppointmentStatusEnum;
use App\Http\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\DentistBlockedSlot;
use Carbon\Carbon;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
    $this->receptionist = User::factory()->create(['role' => RoleEnum::RECEPTIONIST]);
    $this->dentistUser = User::factory()->create(['role' => RoleEnum::DENTIST]);
    $this->dentist = Dentist::factory()->create(['user_id' => $this->dentistUser->id]);

    $this->otherDentistUser = User::factory()->create(['role' => RoleEnum::DENTIST]);
    $this->otherDentist = Dentist::factory()->create(['user_id' => $this->otherDentistUser->id]);

    $this->patient = Patient::factory()->create();
    $this->appointmentType = AppointmentType::factory()->create();
});

it('admin może tworzyć dentystę', function () {
    $token = $this->admin->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.dentist.store'), ['data' => ['attributes' => [
            'first_name' => 'Jan', 'last_name' => 'Kowalski',
        ]]]);

    $response->assertStatus(201);
});

it('recepcjonistka nie może tworzyć dentysty', function () {
    $token = $this->receptionist->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.dentist.store'), ['data' => ['attributes' => [
            'first_name' => 'Jan', 'last_name' => 'Kowalski',
        ]]]);

    $response->assertStatus(403);
});

it('dentysta nie może tworzyć dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.dentist.store'), ['data' => ['attributes' => [
            'first_name' => 'Jan', 'last_name' => 'Kowalski',
        ]]]);

    $response->assertStatus(403);
});

it('admin może przeglądać pacjentów', function () {
    $token = $this->admin->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.patient.index'));

    $response->assertStatus(200);
});

it('recepcjonistka może przeglądać pacjentów', function () {
    $token = $this->receptionist->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.patient.index'));

    $response->assertStatus(200);
});

it('dentysta nie może przeglądać pacjentów', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.patient.index'));

    $response->assertStatus(403);
});

it('recepcjonistka może tworzyć wizytę', function () {
    $token = $this->receptionist->createToken('api test')->plainTextToken;
    $start = now()->addDays(3)->setTime(10, 0);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.appointment.store'), ['data' => ['attributes' => [
            'dentist_id' => $this->dentist->id,
            'patient_id' => $this->patient->id,
            'appointment_type_id' => $this->appointmentType->id,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $start->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            'status' => AppointmentStatusEnum::BOOKED->value,
        ]]]);

    $response->assertStatus(201);
});

it('dentysta nie może tworzyć wizyt', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    $start = now()->addDays(3)->setTime(10, 0);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.appointment.store'), ['data' => ['attributes' => [
            'dentist_id' => $this->dentist->id,
            'patient_id' => $this->patient->id,
            'appointment_type_id' => $this->appointmentType->id,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $start->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            'status' => AppointmentStatusEnum::BOOKED->value,
        ]]]);

    $response->assertStatus(403);
});

it('dentysta widzi tylko swoje wizyty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    Appointment::factory(3)->create(['dentist_id' => $this->dentist->id]);
    Appointment::factory(5)->create(['dentist_id' => $this->otherDentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.appointment.index'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});

it('admin widzi wszystkie wizyty', function () {
    $token = $this->admin->createToken('api test')->plainTextToken;

    Appointment::factory(3)->create(['dentist_id' => $this->dentist->id]);
    Appointment::factory(5)->create(['dentist_id' => $this->otherDentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.appointment.index'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(8);
});

it('dentysta nie widzi wizyty innego dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $otherAppointment = Appointment::factory()->create(['dentist_id' => $this->otherDentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.appointment.show', $otherAppointment));

    $response->assertStatus(403);
});

it('dentysta widzi swoją wizytę', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $appointment = Appointment::factory()->create(['dentist_id' => $this->dentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.appointment.show', $appointment));

    $response->assertStatus(200);
});

it('dentysta nie może tworzyć typów wizyt', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.appointment-type.store'), ['data' => ['attributes' => [
            'name' => 'Przegląd', 'duration_minutes' => 30,
        ]]]);

    $response->assertStatus(403);
});

it('dentysta może przeglądać typy wizyt', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.appointment-type.index'));

    $response->assertStatus(200);
});

it('dentysta może tworzyć blocked slot dla siebie', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->dentist]), ['data' => ['attributes' => [
            'start' => Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'end' => Carbon::tomorrow()->setTime(12, 0)->format('Y-m-d H:i:s'),
        ]]]);

    $response->assertStatus(201);
});

it('dentysta nie może tworzyć blocked slot dla innego dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->otherDentist]), ['data' => ['attributes' => [
            'start' => Carbon::tomorrow()->setTime(10, 0),
            'end' => Carbon::tomorrow()->setTime(12, 0),
        ]]]);

    $response->assertStatus(403);
});

it('dentysta może usunąć swój blocked slot', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    $blockedSlot = DentistBlockedSlot::factory()->create(['dentist_id' => $this->dentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(route('v1.dentist.blocked-slots.destroy', ['dentist' => $this->dentist, 'dentistBlockedSlot' => $blockedSlot]));

    $response->assertStatus(200);
});

it('dentysta nie może usunąć blocked slot innego dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    $blockedSlot = DentistBlockedSlot::factory()->create(['dentist_id' => $this->otherDentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(route('v1.dentist.blocked-slots.destroy', ['dentist' => $this->otherDentist, 'dentistBlockedSlot' => $blockedSlot]));

    $response->assertStatus(403);
});

it('dentysta widzi tylko swoje blocked sloty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    DentistBlockedSlot::factory(2)->create(['dentist_id' => $this->dentist->id]);
    DentistBlockedSlot::factory(3)->create(['dentist_id' => $this->otherDentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.dentist.blocked-slots.index', ['dentist' => $this->dentist]));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});

it('dentysta nie może przeglądać blocked slotów innego dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.dentist.blocked-slots.index', ['dentist' => $this->otherDentist]));

    $response->assertStatus(403);
});

it('admin może przeglądać blocked sloty dowolnego dentysty', function () {
    $token = $this->admin->createToken('api test')->plainTextToken;
    DentistBlockedSlot::factory(3)->create(['dentist_id' => $this->otherDentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('v1.dentist.blocked-slots.index', ['dentist' => $this->otherDentist]));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});

it('dentysta może potwierdzić swoją wizytę', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    $appointment = Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.appointment.confirm', $appointment));

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe(AppointmentStatusEnum::CONFIRMED->value);
});

it('dentysta nie może potwierdzić wizyty innego dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    $appointment = Appointment::factory()->create([
        'dentist_id' => $this->otherDentist->id,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.appointment.confirm', $appointment));

    $response->assertStatus(403);
});

it('dentysta nie może anulować wizyty innego dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    $appointment = Appointment::factory()->create([
        'dentist_id' => $this->otherDentist->id,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.appointment.cancel', $appointment));

    $response->assertStatus(403);
});

it('dentysta nie może zakończyć wizyty innego dentysty', function () {
    $token = $this->dentistUser->createToken('api test')->plainTextToken;
    $appointment = Appointment::factory()->create([
        'dentist_id' => $this->otherDentist->id,
        'status' => AppointmentStatusEnum::CONFIRMED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.appointment.complete', $appointment));

    $response->assertStatus(403);
});
