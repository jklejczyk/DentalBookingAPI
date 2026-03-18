<?php


use App\Models\User;

test('logowanie użytkownika', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('login'),
        [
            'email' => $user->email,
            'password' => 'demo123',
        ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['token']]);
    $this->expect($response->json()['data']['token'])->not->toBeEmpty();
});

test('niepoprawne hasło', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('login'),
        [
            'email' => $user->email,
            'password' => 'asdasdasd',
        ]);

    $response->assertStatus(401);
    $this->expect($response->json('data.token'))->toBeNull();
});

test('nieistniejący email', function () {
    $response = $this->postJson(route('login'),
        [
            'email' => 'losowy@email.com',
            'password' => 'asdasdasd',
        ]);

    $response->assertStatus(401);
    $this->expect($response->json('data.token'))->toBeNull();
});

test('brak danych logowania', function () {
    $response = $this->postJson(route('login'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'password']);
});

test('pomyśle wylogowanie', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson(route('logout'));

    $response->assertStatus(200);
});

test('brak tokenu przy wylogowaniu', function () {
    $response = $this->postJson(route('logout'));

    $response->assertStatus(401);
});

test('niepoprawny token przy wylogowaniu', function () {
    $response = $this->withHeader('Authorization', "Bearer 123456789")
        ->postJson(route('logout'));

    $response->assertStatus(401);
});

test('token nie działa po wylogowaniu', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer $token")
        ->postJson(route('logout'));

    $this->app['auth']->forgetGuards();

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson(route('logout'));

    $response->assertStatus(401);
});
