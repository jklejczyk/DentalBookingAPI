<?php

use App\Models\AppointmentType;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $user = User::factory()->create();
    $this->token = $user->createToken('api test')->plainTextToken;
});

it('zwraca listę typów wizyt', function () {
    AppointmentType::factory(20)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment-type.index'));

    $response->assertStatus(200);
    expect($response->json('data'))->not->toBeEmpty();
});

it('filtruje listę typów wizyt z name zwierającym string', function () {
    AppointmentType::factory(18)->create();
    AppointmentType::factory(2)->create(['name' => 'test']);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment-type.index', ['name' => 'test']));

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('sortuj listę typów wizyt wg daty utworzenia asc', function () {
    AppointmentType::factory()->create(['created_at' => Carbon::today()]);
    $appointment = AppointmentType::factory()->create(['created_at' => Carbon::yesterday()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment-type.index', ['sort' => 'createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.name'))->toBe($appointment->name);
});

it('zwraca 401 przy odpytaniu listy typów wiyzyt bez tokenu', function () {
    AppointmentType::factory(20)->create();

    $response = $this->getJson(route('v1.appointment-type.index'));

    $response->assertStatus(401);
});

it('sortuj listę typów wizyt wg daty utworzenia desc', function () {
    AppointmentType::factory()->create(['created_at' => Carbon::yesterday()]);
    $appointment = AppointmentType::factory()->create(['created_at' => Carbon::today()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment-type.index', ['sort' => '-createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.name'))->toBe($appointment->name);
});

it('utwórz nowy typ wizyty', function () {
    $appointmentTypeData = AppointmentType::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $appointmentTypeData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment-type.store'), $formData);

    $response->assertStatus(201);
    $this->expect($response->json()['data']['attributes'])->not->toBeEmpty();
    $this->assertDatabaseHas('appointment_types', ['name' => $appointmentTypeData['name']]);
});

it('zwraca 422 przy braku podania danych podczas tworzenia nowego typu wizyty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.appointment-type.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.name', 'data.attributes.duration_minutes']);

});

it('zwraca 401 przy braku tokenu podczas tworzenia nowego typu wizyty', function () {
    $appointmentTypeData = AppointmentType::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $appointmentTypeData]];

    $response = $this->postJson(route('v1.appointment-type.store'), $formData);

    $response->assertStatus(401);
});

it('zwraca 401 przy braku tokenu podczas aktualizacji typu wizyty', function () {
    $appointmentType = AppointmentType::factory()->create();
    $formData = ['data' => ['attributes' => ['name' => 'nowa nazwa', 'duration_minutes' => 30]]];

    $response = $this->patchJson(route('v1.appointment-type.update', $appointmentType), $formData);

    $response->assertStatus(401);
});

it('aktualizuj typ wizyty', function () {
    $appointmentType = AppointmentType::factory()->create();

    $formData = ['data' => ['attributes' => ['name' => 'nowa nazwa', 'duration_minutes' => 30]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment-type.update', $appointmentType), $formData);

    $response->assertStatus(200);
    expect($response->json('data.attributes.name'))->toBe('nowa nazwa');
    $this->assertDatabaseHas('appointment_types', ['id' => $appointmentType->id, 'name' => 'nowa nazwa']);
});

it('zwraca 422 przy braku danych podczas aktualizacji typu wizyty', function () {
    $appointmentType = AppointmentType::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment-type.update', $appointmentType), []);

    $response->assertStatus(422);
});

it('zwraca 404 przy aktualizacji nieistniejącego typu wizyty', function () {
    $formData = ['data' => ['attributes' => ['name' => 'nowa nazwa', 'duration_minutes' => 30]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.appointment-type.update', 99999), $formData);

    $response->assertStatus(404);
});

it('usuń typ wizyty', function () {
    $appointmentType = AppointmentType::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.appointment-type.destroy', $appointmentType));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('appointment_types', ['id' => $appointmentType->id]);
});

it('zwraca 404 przy usuwaniu nieistniejącego typu wizyty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.appointment-type.destroy', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy braku tokenu podczas usuwania typu wizyty', function () {
    $appointmentType = AppointmentType::factory()->create();

    $response = $this->deleteJson(route('v1.appointment-type.destroy', $appointmentType));

    $response->assertStatus(401);
});

it('zwraca pojedynczy typ wizyty', function () {
    $appointmentType = AppointmentType::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment-type.show', $appointmentType));

    $response->assertStatus(200);
    expect($response->json('data.attributes.name'))->toBe($appointmentType->name);
});

it('zwraca 404 przy pobraniu nieistniejącego typu wizyty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.appointment-type.show', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy pobraniu typu wizyty bez tokenu', function () {
    $appointmentType = AppointmentType::factory()->create();

    $response = $this->getJson(route('v1.appointment-type.show', $appointmentType));

    $response->assertStatus(401);
});
