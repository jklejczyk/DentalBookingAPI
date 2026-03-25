<?php

use App\Http\Enums\AppointmentStatusEnum;
use App\Mail\AppointmentStatusChanged;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $user = User::factory()->create();
    $this->token = $user->createToken('api test')->plainTextToken;
    $this->appointmentTypes = AppointmentType::factory(5)->create();
    Dentist::factory(5)->create();
    Patient::factory(20)->create();
});

it('zwraca listę wizyt', function () {
    Appointment::factory(20)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.index'));

    $response->assertStatus(200);
    expect($response->json('data'))->not->toBeEmpty();
});

it('filtruje listę wizyt na zgodne z appointment_type', function () {
    Appointment::factory(18)->create(['appointment_type_id' => $this->appointmentTypes[1]->id]);
    Appointment::factory(2)->create(['appointment_type_id' => $this->appointmentTypes[0]->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.index', ['appointment_type' => $this->appointmentTypes[0]->id]));

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('sortuj listę wizyt wg daty utworzenia asc', function () {
    Appointment::factory()->create(['created_at' => Carbon::today()]);
    $appointment = Appointment::factory()->create(['created_at' => Carbon::yesterday()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.index', ['sort' => 'createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.name'))->toBe($appointment->name);
});

it('zwraca 401 przy odpytaniu listy wiyzy bez tokenu', function () {
    Appointment::factory(20)->create();

    $response = $this->getJson(route('v1.appointment.index'));

    $response->assertStatus(401);
});

it('sortuj listę wizyt wg daty utworzenia desc', function () {
    Appointment::factory()->create(['created_at' => Carbon::yesterday()]);
    $appointment = Appointment::factory()->create(['created_at' => Carbon::today()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.index', ['sort' => '-createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.name'))->toBe($appointment->name);
});

it('utwórz nowy wizytę', function () {
    $appointmentData = Appointment::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $appointmentData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.store'), $formData);

    $response->assertStatus(201);
    $this->expect($response->json()['data']['attributes'])->not->toBeEmpty();
    $this->assertDatabaseHas('appointments', ['start' => Carbon::parse($appointmentData['start'])->format('Y-m-d H:i:s')]);
});

it('zwraca 422 przy braku podania danych podczas tworzenia nowej wiyzty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.status', 'data.attributes.appointment_type_id']);

});

it('zwraca 401 przy braku tokenu podczas tworzenia nowej wizyty', function () {
    $appointmentData = Appointment::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $appointmentData]];

    $response = $this->postJson(route('v1.appointment.store'), $formData);

    $response->assertStatus(401);
});

it('zwraca 401 przy braku tokenu podczas aktualizacji wizyty', function () {
    $appointment = Appointment::factory()->create();
    $formData = ['data' => ['attributes' => ['name' => 'nowa nazwa', 'duration_minutes' => 30]]];

    $response = $this->patchJson(route('v1.appointment.update', $appointment), $formData);

    $response->assertStatus(401);
});

it('aktualizuj wizytę', function () {
    $appointment = Appointment::factory()->create();
    $appointmentData = Appointment::factory()->make()->toArray();
    $appointmentData['status'] = AppointmentStatusEnum::COMPLETED;
    $formData = ['data' => ['attributes' => $appointmentData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment.update', $appointment), $formData);

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe(AppointmentStatusEnum::COMPLETED->value);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => AppointmentStatusEnum::COMPLETED->value]);
});

it('zwraca 422 przy nieprawidłowych danych podczas aktualizacji wizyty', function () {
    $appointment = Appointment::factory()->create();

    $formData = ['data' => ['attributes' => [
        'status' => 'nieprawidlowy_status',
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment.update', $appointment), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.status']);
});

it('zwraca 404 przy aktualizacji nieistniejącej wizyty', function () {
    $appointmentData = Appointment::factory()->make()->toArray();
    $appointmentData['status'] = AppointmentStatusEnum::COMPLETED;
    $formData = ['data' => ['attributes' => $appointmentData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment.update', 99999), $formData);

    $response->assertStatus(404);
});

it('zwraca 422 przy konflikcie terminów podczas aktualizacji', function () {
    $dentist = Dentist::first();
    $patient = Patient::first();
    $appointmentType = $this->appointmentTypes[0];

    $start = Carbon::parse('next monday')->setTime(10, 0);
    $end = $start->copy()->addMinutes((int) $appointmentType->duration_minutes);

    $existingAppointment = Appointment::factory()->create([
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start,
        'end' => $end,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $laterStart = $end->copy();
    $laterEnd = $laterStart->copy()->addMinutes((int) $appointmentType->duration_minutes);

    $appointmentToUpdate = Appointment::factory()->create([
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $laterStart,
        'end' => $laterEnd,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    // Próbujemy przesunąć drugą wizytę na termin pierwszej
    $formData = ['data' => ['attributes' => [
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s'),
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment.update', $appointmentToUpdate), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.start']);
});

it('pozwala na aktualizację wizyty bez zmiany terminu', function () {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $formData = ['data' => ['attributes' => [
        'status' => AppointmentStatusEnum::CONFIRMED->value,
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment.update', $appointment), $formData);

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe(AppointmentStatusEnum::CONFIRMED->value);
});

it('usuń wizytę', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.appointment.destroy', $appointment));

    $response->assertStatus(200);
    $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
});

it('zwraca 404 przy usuwaniu nieistniejącej wizyty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.appointment.destroy', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy braku tokenu podczas usuwania wizyty', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->deleteJson(route('v1.appointment.destroy', $appointment));

    $response->assertStatus(401);
});

it('zwraca pojedynczą wizytę', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.show', $appointment));

    $response->assertStatus(200);
    expect($response->json('data.attributes.name'))->toBe($appointment->name);
});

it('zwraca 404 przy pobraniu nieistniejącej wizyty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.show', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy pobraniu wizyty bez tokenu', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->getJson(route('v1.appointment.show', $appointment));

    $response->assertStatus(401);
});

it('oznacz wizytę jako potwierdzoną', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::BOOKED]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.confirm', $appointment));

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe(AppointmentStatusEnum::CONFIRMED->value);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => AppointmentStatusEnum::CONFIRMED->value]);
    Mail::assertQueued(AppointmentStatusChanged::class);
});

it('oznacz wizytę jako odwołaną', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::BOOKED]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.cancel', $appointment));

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe(AppointmentStatusEnum::CANCELLED->value);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => AppointmentStatusEnum::CANCELLED->value]);
    Mail::assertQueued(AppointmentStatusChanged::class);
});

it('oznacz wizytę jako zakończoną', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::CONFIRMED]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.complete', $appointment));

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe(AppointmentStatusEnum::COMPLETED->value);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => AppointmentStatusEnum::COMPLETED->value]);
    Mail::assertQueued(AppointmentStatusChanged::class);
});

it('anuluje potwierdzoną wizytę z więcej niż 24h wyprzedzenia', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatusEnum::CONFIRMED,
        'start' => Carbon::now()->addHours(48),
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.cancel', $appointment));

    $response->assertStatus(200);
    expect($response->json('data.attributes.status'))->toBe(AppointmentStatusEnum::CANCELLED->value);
    Mail::assertQueued(AppointmentStatusChanged::class);
});

it('nie pozwala anulować potwierdzonej wizyty z mniej niż 24h wyprzedzenia', function () {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatusEnum::CONFIRMED,
        'start' => Carbon::now()->addHours(12),
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.cancel', $appointment));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['status']);
});

it('nie pozwala potwierdzić anulowanej wizyty', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::CANCELLED]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.confirm', $appointment));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['status']);
});

it('nie pozwala potwierdzić zakończonej wizyty', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::COMPLETED]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.confirm', $appointment));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['status']);
});

it('nie pozwala zakończyć wizyty ze statusu booked', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::BOOKED]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.complete', $appointment));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['status']);
});

it('nie pozwala anulować zakończonej wizyty', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::COMPLETED]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.cancel', $appointment));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['status']);
});

it('zwraca 422 przy konflikcie terminów', function () {
    $dentist = Dentist::first();
    $patient = Patient::first();
    $appointmentType = $this->appointmentTypes[0];

    $start = Carbon::parse('next monday')->setTime(10, 0);
    $end = $start->copy()->addMinutes((int) $appointmentType->duration_minutes);

    Appointment::factory()->create([
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start,
        'end' => $end,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $formData = ['data' => ['attributes' => [
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s'),
        'status' => AppointmentStatusEnum::BOOKED->value,
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.store'), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.start']);
});

it('pozwala na wizytę gdy istniejąca jest anulowana', function () {
    $dentist = Dentist::first();
    $patient = Patient::first();
    $appointmentType = $this->appointmentTypes[0];

    $start = Carbon::parse('next monday')->setTime(10, 0);
    $end = $start->copy()->addMinutes((int) $appointmentType->duration_minutes);

    Appointment::factory()->create([
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start,
        'end' => $end,
        'status' => AppointmentStatusEnum::CANCELLED,
    ]);

    $formData = ['data' => ['attributes' => [
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s'),
        'status' => AppointmentStatusEnum::BOOKED->value,
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.store'), $formData);

    $response->assertStatus(201);
});

it('pozwala na wizytę w innym terminie tego samego dentysty', function () {
    $dentist = Dentist::first();
    $patient = Patient::first();
    $appointmentType = $this->appointmentTypes[0];

    $start = Carbon::parse('next monday')->setTime(10, 0);
    $end = $start->copy()->addMinutes((int) $appointmentType->duration_minutes);

    Appointment::factory()->create([
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start,
        'end' => $end,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    $laterStart = $end->copy();
    $laterEnd = $laterStart->copy()->addMinutes((int) $appointmentType->duration_minutes);

    $formData = ['data' => ['attributes' => [
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $laterStart->format('Y-m-d H:i:s'),
        'end' => $laterEnd->format('Y-m-d H:i:s'),
        'status' => AppointmentStatusEnum::BOOKED->value,
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.store'), $formData);

    $response->assertStatus(201);
});

it('zwraca 422 przy częściowym nakładaniu terminów', function () {
    $dentist = Dentist::first();
    $patient = Patient::first();
    $appointmentType = $this->appointmentTypes[0];

    $start = Carbon::parse('next monday')->setTime(10, 0);
    $end = $start->copy()->addMinutes(60);

    Appointment::factory()->create([
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start,
        'end' => $end,
        'status' => AppointmentStatusEnum::BOOKED,
    ]);

    // Nowa wizyta zaczyna się 30 min po starcie istniejącej — nachodzi
    $formData = ['data' => ['attributes' => [
        'dentist_id' => $dentist->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'start' => $start->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
        'end' => $end->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
        'status' => AppointmentStatusEnum::BOOKED->value,
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment.store'), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.start']);
});

it('usunięta wizyta nadal istnieje w bazie', function () {
    $appointment = Appointment::factory()->create();
    $appointment->delete();

    $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    expect(Appointment::withTrashed()->find($appointment->id))->not->toBeNull();
});

it('zwraca 404 przy pobraniu usuniętej wizyty', function () {
    $appointment = Appointment::factory()->create();
    $appointment->delete();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.show', $appointment));

    $response->assertStatus(404);
});

it('usunięta wizyta nie pojawia się w liście', function () {
    $appointment = Appointment::factory()->create();
    $appointment->delete();

    Appointment::factory(2)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment.index'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});
