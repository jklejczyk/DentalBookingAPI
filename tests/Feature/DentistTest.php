<?php

use App\Models\Dentist;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $user = User::factory()->create();
    $this->token = $user->createToken('api test')->plainTextToken;
});

test('zwraca listę dentystów', function () {
    Dentist::factory(20)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.index'));

    $response->assertStatus(200);
    expect($response->json('data'))->not->toBeEmpty();
});

it('filtruje dentystów z first_name zwierającym string', function () {
    Dentist::factory(18)->create();
    Dentist::factory(2)->create(['first_name' => 'test']);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.index', ['first_name' => 'test']));

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('sortuj dentystów wg daty utworzenia asc', function () {
    Dentist::factory()->create(['created_at' => Carbon::today()]);
    $dentist = Dentist::factory()->create(['created_at' => Carbon::yesterday()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.index', ['sort' => 'createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.first_name'))->toBe($dentist->first_name);
});

it('zwraca 401 przy odpytaniu listy typów wiyzyt bez tokenu', function () {
    Dentist::factory(20)->create();

    $response = $this->getJson(route('v1.dentist.index'));

    $response->assertStatus(401);
});

it('sortuj dentystów wg daty utworzenia desc', function () {
    Dentist::factory()->create(['created_at' => Carbon::yesterday()]);
    $dentist = Dentist::factory()->create(['created_at' => Carbon::today()]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.index', ['sort' => '-createdAt']));

    $response->assertStatus(200);
    expect($response->json('data.0.attributes.first_name'))->toBe($dentist->first_name);
});

it('utwórz nowego dentysty', function () {
    $dentistData = Dentist::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $dentistData]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.store'), $formData);

    $response->assertStatus(201);
    $this->expect($response->json()['data']['attributes'])->not->toBeEmpty();
    $this->assertDatabaseHas('dentists', ['first_name' => $dentistData['first_name']]);
});

it('zwraca 422 przy braku podania danych podczas tworzenia nowego dentysty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson(route('v1.dentist.store'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.attributes.first_name', 'data.attributes.last_name']);

});

it('zwraca 401 przy braku tokenu podczas tworzenia nowego dentysty', function () {
    $dentistData = Dentist::factory()->make()->toArray();
    $formData = ['data' => ['attributes' => $dentistData]];

    $response = $this->postJson(route('v1.dentist.store'), $formData);

    $response->assertStatus(401);
});

it('zwraca 401 przy braku tokenu podczas aktualizacji dentysty', function () {
    $dentist = Dentist::factory()->create();
    $formData = ['data' => ['attributes' => ['first_name' => 'nowe imie', 'last_name' => 'nowe nazwisko']]];

    $response = $this->patchJson(route('v1.dentist.update', $dentist), $formData);

    $response->assertStatus(401);
});

it('aktualizuj dentysty', function () {
    $dentist = Dentist::factory()->create();

    $formData = ['data' => ['attributes' => ['first_name' => 'nowe imie', 'last_name' => 'nowe nazwisko']]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.dentist.update', $dentist), $formData);

    $response->assertStatus(200);
    expect($response->json('data.attributes.first_name'))->toBe('nowe imie');
    $this->assertDatabaseHas('dentists', ['id' => $dentist->id, 'first_name' => 'nowe imie']);
});

it('zwraca 422 przy braku danych podczas aktualizacji dentysty', function () {
    $dentist = Dentist::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.dentist.update', $dentist), []);

    $response->assertStatus(422);
});

it('zwraca 404 przy aktualizacji nieistniejącego dentysty', function () {
    $formData = ['data' => ['attributes' => ['first_name' => 'nowe imie', 'last_name' => 'nowe nazwisko']]];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson(route('v1.dentist.update', 99999), $formData);

    $response->assertStatus(404);
});

it('usuń dentysty', function () {
    $dentist = Dentist::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.dentist.destroy', $dentist));

    $response->assertStatus(200);
    $this->assertSoftDeleted('dentists', ['id' => $dentist->id]);
});

it('zwraca 404 przy usuwaniu nieistniejącego dentysty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson(route('v1.dentist.destroy', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy braku tokenu podczas usuwania dentysty', function () {
    $dentist = Dentist::factory()->create();

    $response = $this->deleteJson(route('v1.dentist.destroy', $dentist));

    $response->assertStatus(401);
});

it('zwraca pojedynczego dentysty', function () {
    $dentist = Dentist::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.show', $dentist));

    $response->assertStatus(200);
    expect($response->json('data.attributes.first_name'))->toBe($dentist->first_name);
});

it('zwraca 404 przy pobraniu nieistniejącego dentysty', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.show', 99999));

    $response->assertStatus(404);
});

it('zwraca 401 przy pobraniu dentysty bez tokenu', function () {
    $dentist = Dentist::factory()->create();

    $response = $this->getJson(route('v1.dentist.show', $dentist));

    $response->assertStatus(401);
});

it('usunięty dentysta nadal istnieje w bazie', function () {
    $dentist = Dentist::factory()->create();
    $dentist->delete();

    $this->assertSoftDeleted('dentists', ['id' => $dentist->id]);
    expect(Dentist::withTrashed()->find($dentist->id))->not->toBeNull();
});

it('zwraca 404 przy pobraniu usuniętego dentysty', function () {
    $dentist = Dentist::factory()->create();
    $dentist->delete();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.show', $dentist));

    $response->assertStatus(404);
});

it('usunięty dentysta nie pojawia się w liście', function () {
    $dentist = Dentist::factory()->create();
    $dentist->delete();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson(route('v1.dentist.index'));

    $response->assertStatus(200);

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->not->toContain($dentist->id);
});
