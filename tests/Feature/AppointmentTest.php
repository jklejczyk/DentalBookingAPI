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

it('filtruje listę wizyt z appointment_type zwierającym string', function () {
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

it('zwraca 422 przy braku danych podczas aktualizacji wizyty', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment.update', $appointment), []);

    $response->assertStatus(422);
});

it('zwraca 404 przy aktualizacji nieistniejącej wizyty', function () {
    $appointmentData = Appointment::factory()->make()->toArray();
    $appointmentData['status'] = AppointmentStatusEnum::COMPLETED;
    $formData = ['data' => ['attributes' => $appointmentData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment.update', 99999), $formData);

    $response->assertStatus(404);
});

it('usuń wizytę', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.appointment.destroy', $appointment));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
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
