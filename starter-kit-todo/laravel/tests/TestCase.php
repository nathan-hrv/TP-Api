<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public const ADMIN_USERNAME = 'admin';
    public const ADMIN_PASSWORD = 'Admin-Test-Password-1';

    /** @var array{0: string, 1: string}|null paire de clés RSA générée une seule fois pour tous les tests */
    private static ?array $keys = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Les tests génèrent leurs propres clés RSA : ils ne dépendent pas du volume de clés de Docker Compose
        self::$keys ??= self::generateKeyPair();
        config([
            'jwt.private_key' => self::$keys[0],
            'jwt.public_key'  => self::$keys[1],
            'todo.users.admin.password' => self::ADMIN_PASSWORD,
            'todo.users.user.password'  => 'User-Test-Password-1',
        ]);

        // Mémoire APCu vidée : chaque test repart d'une liste vide et d'un compteur à zéro
        apcu_clear_cache();
    }

    private static function generateKeyPair(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($resource, $private);

        return [$private, openssl_pkey_get_details($resource)['key']];
    }

    /** Obtient un jeton en appelant la vraie route de login. */
    protected function loginAs(string $username, string $password): string
    {
        return $this->postJson('/api/v1/auth/login', ['username' => $username, 'password' => $password])
            ->assertOk()
            ->json('token');
    }
}
