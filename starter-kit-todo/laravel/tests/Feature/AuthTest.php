<?php

/* Tests du socle fourni : login, route protégée et route publique. Verts dès le départ. */

use Tests\TestCase;

it('émet un jeton Bearer pour des identifiants valides', function () {
    $this->postJson('/api/v1/auth/login', ['username' => TestCase::ADMIN_USERNAME, 'password' => TestCase::ADMIN_PASSWORD])
        ->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'expires_at'])
        ->assertJsonPath('token_type', 'Bearer');
});

it('renvoie le même message pour un compte inconnu et un mauvais mot de passe', function () {
    $wrongPassword = $this->postJson('/api/v1/auth/login', ['username' => TestCase::ADMIN_USERNAME, 'password' => 'faux']);
    $unknownUser = $this->postJson('/api/v1/auth/login', ['username' => 'inconnu', 'password' => 'faux']);

    $wrongPassword->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
    $unknownUser->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
});

it('valide le corps du login', function () {
    $this->postJson('/api/v1/auth/login', ['username' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username', 'password']);
});

it('refuse une route protégée sans jeton, avec WWW-Authenticate', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer')
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('refuse une route protégée avec un jeton invalide', function () {
    $this->getJson('/api/v1/me', ['Authorization' => 'Bearer pas-un-jwt'])->assertUnauthorized();
});

it('renvoie l\'identité lue dans le jeton', function () {
    $token = $this->loginAs(TestCase::ADMIN_USERNAME, TestCase::ADMIN_PASSWORD);

    $this->getJson('/api/v1/me', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertExactJson(['data' => ['username' => 'admin', 'roles' => ['admin']]]);
});

it('renvoie une erreur 404 JSON pour une route inconnue', function () {
    $this->getJson('/api/v1/route-inexistante')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Resource not found.']);
});
