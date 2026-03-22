<?php

use App\Http\Enums\AppointmentStatusEnum;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->dentist = Dentist::factory()->create();
    $this->patient = Patient::factory()->create(['email' => 'test@example.com']);
    $this->appointmentType = AppointmentType::factory()->create();
});

it('wysyła przypomnienia o jutrzejszych wizytach', function () {
    Mail::fake();

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::tomorrow()->setTime(10, 0),
        'end' => Carbon::tomorrow()->setTime(10, 30),
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $this->artisan('appointments:send-reminders')
        ->expectsOutputToContain('Wysłano przypomnień: 1')
        ->assertSuccessful();

    Mail::assertQueued(AppointmentReminder::class, 1);
});

it('wysyła przypomnienia dla wizyt ze statusem confirmed', function () {
    Mail::fake();

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::tomorrow()->setTime(14, 0),
        'end' => Carbon::tomorrow()->setTime(14, 30),
        'status' => AppointmentStatusEnum::CONFIRMED,
    ]);

    $this->artisan('appointments:send-reminders')->assertSuccessful();

    Mail::assertQueued(AppointmentReminder::class, 1);
});

it('nie wysyła przypomnień dla anulowanych wizyt', function () {
    Mail::fake();

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::tomorrow()->setTime(10, 0),
        'end' => Carbon::tomorrow()->setTime(10, 30),
        'status' => AppointmentStatusEnum::CANCELLED,
    ]);

    $this->artisan('appointments:send-reminders')
        ->expectsOutputToContain('Wysłano przypomnień: 0')
        ->assertSuccessful();

    Mail::assertNotQueued(AppointmentReminder::class);
});

it('nie wysyła przypomnień dla wizyt na pojutrze', function () {
    Mail::fake();

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::tomorrow()->addDay()->setTime(10, 0),
        'end' => Carbon::tomorrow()->addDay()->setTime(10, 30),
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $this->artisan('appointments:send-reminders')
        ->expectsOutputToContain('Wysłano przypomnień: 0')
        ->assertSuccessful();

    Mail::assertNotQueued(AppointmentReminder::class);
});

it('nie wysyła przypomnień dla dzisiejszych wizyt', function () {
    Mail::fake();

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::today()->setTime(15, 0),
        'end' => Carbon::today()->setTime(15, 30),
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $this->artisan('appointments:send-reminders')
        ->expectsOutputToContain('Wysłano przypomnień: 0')
        ->assertSuccessful();

    Mail::assertNotQueued(AppointmentReminder::class);
});

it('nie wysyła przypomnień gdy pacjent nie ma emaila', function () {
    Mail::fake();

    $patientNoEmail = Patient::factory()->create(['email' => null]);

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $patientNoEmail->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::tomorrow()->setTime(10, 0),
        'end' => Carbon::tomorrow()->setTime(10, 30),
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $this->artisan('appointments:send-reminders')
        ->expectsOutputToContain('Wysłano przypomnień: 0')
        ->assertSuccessful();

    Mail::assertNotQueued(AppointmentReminder::class);
});

it('wysyła wiele przypomnień dla wielu wizyt', function () {
    Mail::fake();

    $patient2 = Patient::factory()->create(['email' => 'drugi@example.com']);

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $this->patient->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::tomorrow()->setTime(10, 0),
        'end' => Carbon::tomorrow()->setTime(10, 30),
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    Appointment::factory()->create([
        'dentist_id' => $this->dentist->id,
        'patient_id' => $patient2->id,
        'appointment_type_id' => $this->appointmentType->id,
        'start' => Carbon::tomorrow()->setTime(11, 0),
        'end' => Carbon::tomorrow()->setTime(11, 30),
        'status' => AppointmentStatusEnum::CONFIRMED,
    ]);

    $this->artisan('appointments:send-reminders')
        ->expectsOutputToContain('Wysłano przypomnień: 2')
        ->assertSuccessful();

    Mail::assertQueued(AppointmentReminder::class, 2);
});
