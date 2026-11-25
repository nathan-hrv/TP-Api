<?php

namespace App\Services;

/**
 * Annuaire des comptes de démonstration : deux comptes, dont les mots de passe viennent de .env.
 *
 * Simplification de TP : dans une vraie application, les comptes sont en base, avec des mots de passe HACHÉS
 * (bcrypt, argon2) : on ne compare jamais un mot de passe en clair.
 */
final class UserDirectory
{
    private const DUMMY = 'dummy-value-so-that-comparison-always-happens';

    /** Renvoie le rôle de l'utilisateur si les identifiants sont valides, sinon null. */
    public function authenticate(string $username, string $password): ?string
    {
        $account = config('todo.users')[$username] ?? null;
        $expected = (string) ($account['password'] ?? '');

        // Comparaison faite même pour un compte inconnu, et en temps constant : le temps de réponse ne trahit rien
        $same = hash_equals($expected !== '' ? $expected : self::DUMMY, $password);

        return ($account !== null && $expected !== '' && $same) ? $account['role'] : null;
    }
}
