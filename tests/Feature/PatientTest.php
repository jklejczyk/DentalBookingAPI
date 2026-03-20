<?php

use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $user = User::factory()->create();
    $this->token = $user->createToken('api test')->plainTextToken;
});

test('zwraca listę pacjentów', function () {
    Patient::factory(20)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.patient.index'));


    $response->assertStatus(200);
    expect($response->json('data'))->not->toBeEmpty();
});

it('filtruje pacjentów z first_name zwierającym string', function () {
    Patient::factory(18)->create();
    Patient::factory(2)->create(['first_name' => 'test']);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.patient.index', ['first_name' => 'test']));

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('sortuj pacjentów wg daty utworzenia asc', function () {
    Patient::factory()->create(['created_at' => Carbon::today()]);
    $patient = Patient::factory()->create(['created_at' => Carbon::yesterday()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.patient.index', ['sort' => 'createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.first_name'))->toBe($patient->first_name);
});

it('zwraca 401 przy odpytaniu listy typów wiyzyt bez tokenu', function () {
    Patient::factory(20)->create();

    $response = $this->getJson(route('v1.patient.index'));

    $response->assertStatus(401);
});

it('sortuj pacjentów wg daty utworzenia desc', function () {
    Patient::factory()->create(['created_at' => Carbon::yesterday()]);
    $patient = Patient::factory()->create(['created_at' => Carbon::today()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.patient.index', ['sort' => '-createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.first_name'))->toBe($patient->first_name);
});

it('utwórz nowego pacjenta', function () {
    $patientData = Patient::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $patientData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.patient.store'), $formData);

    $response->assertStatus(201);
    $this->expect($response->json()['data']['attributes'])->not->toBeEmpty();
    $this->assertDatabaseHas('patients', ['first_name' => $patientData['first_name']]);
});

it('zwraca 422 przy braku podania danych podczas tworzenia nowego pacjenta', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.patient.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.first_name', 'data.attributes.last_name']);

});

it('zwraca 401 przy braku tokenu podczas tworzenia nowego pacjenta', function () {
    $patientData = Patient::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $patientData]];

    $response = $this->postJson(route('v1.patient.store'), $formData);

    $response->assertStatus(401);
});

it('zwraca 401 przy braku tokenu podczas aktualizacji pacjenta', function () {
    $patient = Patient::factory()->create();
    $formData = ['data' => ['attributes' => ['first_name' => 'nowe imie', 'last_name' => 'nowe nazwisko']]];

    $response = $this->patchJson(route('v1.patient.update', $patient), $formData);

    $response->assertStatus(401);
});

it('aktualizuj pacjenta', function () {
    $patient = Patient::factory()->create();

    $formData = ['data' => ['attributes' => ['first_name' => 'nowe imie', 'last_name' => 'nowe nazwisko', 'birthday' => Carbon::today(), 'pesel' => '11111111111']]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.patient.update', $patient), $formData);

    $response->assertStatus(200);
    expect($response->json('data.attributes.first_name'))->toBe( 'nowe imie');
    $this->assertDatabaseHas('patients', ['id' => $patient->id, 'first_name' =>  'nowe imie']);
});

it('zwraca 422 przy braku danych podczas aktualizacji pacjenta', function () {
    $patient = Patient::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.patient.update', $patient), []);

    $response->assertStatus(422);
});

it('zwraca 422 gdy płeć spoza zakresu podczas aktualizacji apcjenta', function () {
    $patient = Patient::factory()->create();
    $patientData = $patient->toArray();

    $patientData['gender'] = 'D';
    $formData = ['data' => ['attributes' => $patientData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.patient.update', $patient), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.gender']);
});

it('zwraca 422 gdy pesel zbyt długi podczas aktualizacji apcjenta', function () {
    $patient = Patient::factory()->create();
    $patientData = $patient->toArray();

    $patientData['pesel'] = '123456789111';
    $formData = ['data' => ['attributes' => $patientData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.patient.update', $patient), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.pesel']);
});

it('zwraca 404 przy aktualizacji nieistniejącego pacjenta', function () {
    $formData = ['data' => ['attributes' => ['first_name' => 'nowe imie', 'last_name' => 'nowe nazwisko', 'birthday' => Carbon::today(), 'pesel' => '11111111111']]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.patient.update', 99999), $formData);

    $response->assertStatus(404);
});

it('usuń pacjenta', function () {
    $patient = Patient::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.patient.destroy', $patient));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
});

it('zwraca 404 przy usuwaniu nieistniejącego pacjenta', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.patient.destroy', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy braku tokenu podczas usuwania pacjenta', function () {
    $patient = Patient::factory()->create();

    $response = $this->deleteJson(route('v1.patient.destroy', $patient));

    $response->assertStatus(401);
});

it('zwraca pojedynczego pacjenta', function () {
    $patient = Patient::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.patient.show', $patient));

    $response->assertStatus(200);
    expect($response->json('data.attributes.first_name'))->toBe($patient->first_name);
});

it('zwraca 404 przy pobraniu nieistniejącego pacjenta', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.patient.show', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy pobraniu pacjenta bez tokenu', function () {
    $patient = Patient::factory()->create();

    $response = $this->getJson(route('v1.patient.show', $patient));

    $response->assertStatus(401);
});
