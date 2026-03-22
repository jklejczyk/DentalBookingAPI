<?php

use App\Models\Dentist;
use App\Models\DentistBlockedSlot;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $user = User::factory()->create();
    $this->token = $user->createToken('api test')->plainTextToken;
    $this->dentist = Dentist::factory()->create();
});

it('zwraca listę zablokowanych slotów dentysty', function () {
    DentistBlockedSlot::factory(3)->create(['dentist_id' => $this->dentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.blocked-slots.index', ['dentist' => $this->dentist]));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});

it('nie zwraca slotów innego dentysty', function () {
    $otherDentist = Dentist::factory()->create();
    DentistBlockedSlot::factory(3)->create(['dentist_id' => $otherDentist->id]);
    DentistBlockedSlot::factory(2)->create(['dentist_id' => $this->dentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.blocked-slots.index', ['dentist' => $this->dentist]));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});

it('tworzy zablokowany slot', function () {
    $start = Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i:s');
    $end = Carbon::tomorrow()->setTime(12, 0)->format('Y-m-d H:i:s');

    $formData = ['data' => ['attributes' => [
        'start' => $start,
        'end' => $end,
        'reason' => 'Przerwa obiadowa',
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->dentist]), $formData);

    $response->assertStatus(201);
    $this->assertDatabaseHas('dentist_blocked_slots', [
        'dentist_id' => $this->dentist->id,
        'reason' => 'Przerwa obiadowa',
    ]);
});

it('tworzy zablokowany slot bez powodu', function () {
    $start = Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i:s');
    $end = Carbon::tomorrow()->setTime(12, 0)->format('Y-m-d H:i:s');

    $formData = ['data' => ['attributes' => [
        'start' => $start,
        'end' => $end,
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->dentist]), $formData);

    $response->assertStatus(201);
    $this->assertDatabaseHas('dentist_blocked_slots', [
        'dentist_id' => $this->dentist->id,
        'reason' => null,
    ]);
});

it('zwraca 422 gdy end jest przed start', function () {
    $formData = ['data' => ['attributes' => [
        'start' => Carbon::tomorrow()->setTime(12, 0)->format('Y-m-d H:i:s'),
        'end' => Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i:s'),
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->dentist]), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.end']);
});

it('zwraca 422 gdy start jest w przeszłości', function () {
    $formData = ['data' => ['attributes' => [
        'start' => Carbon::yesterday()->setTime(10, 0)->format('Y-m-d H:i:s'),
        'end' => Carbon::yesterday()->setTime(12, 0)->format('Y-m-d H:i:s'),
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->dentist]), $formData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.start']);
});

it('zwraca 422 bez wymaganych pól', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->dentist]), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.start', 'data.attributes.end']);
});

it('usuwa zablokowany slot', function () {
    $blockedSlot = DentistBlockedSlot::factory()->create(['dentist_id' => $this->dentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.dentist.blocked-slots.destroy', ['dentist' => $this->dentist, 'dentistBlockedSlot' => $blockedSlot]));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('dentist_blocked_slots', ['id' => $blockedSlot->id]);
});

it('zwraca 404 przy usuwaniu nieistniejącego slotu', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.dentist.blocked-slots.destroy', ['dentist' => $this->dentist, 'dentistBlockedSlot' => 99999]));

    $response->assertStatus(404);
});

it('zwraca 401 bez tokenu przy liście slotów', function () {
    $response = $this->getJson(route('v1.dentist.blocked-slots.index', ['dentist' => $this->dentist]));

    $response->assertStatus(401);
});

it('zwraca 401 bez tokenu przy tworzeniu slotu', function () {
    $formData = ['data' => ['attributes' => [
        'start' => Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i:s'),
        'end' => Carbon::tomorrow()->setTime(12, 0)->format('Y-m-d H:i:s'),
    ]]];

    $response = $this->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => $this->dentist]), $formData);

    $response->assertStatus(401);
});

it('zwraca 404 przy liście slotów nieistniejącego dentysty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.blocked-slots.index', ['dentist' => 99999]));

    $response->assertStatus(404);
});

it('zwraca 404 przy tworzeniu slotu dla nieistniejącego dentysty', function () {
    $formData = ['data' => ['attributes' => [
        'start' => Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i:s'),
        'end' => Carbon::tomorrow()->setTime(12, 0)->format('Y-m-d H:i:s'),
    ]]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.blocked-slots.store', ['dentist' => 99999]), $formData);

    $response->assertStatus(404);
});

it('zwraca 404 przy usuwaniu slotu należącego do innego dentysty', function () {
    $otherDentist = Dentist::factory()->create();
    $blockedSlot = DentistBlockedSlot::factory()->create(['dentist_id' => $otherDentist->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.dentist.blocked-slots.destroy', ['dentist' => $this->dentist, 'dentistBlockedSlot' => $blockedSlot]));

    $response->assertStatus(404);
});
