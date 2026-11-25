<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use stdClass;

/**
 * Émission et vérification des JWT (RS256). Fourni : vous apprenez à l'utiliser, pas à l'écrire.
 *
 * Le serveur signe avec sa clé PRIVÉE ; la vérification, à chaque requête, n'utilise que la clé PUBLIQUE :
 * seul ce service peut fabriquer un jeton, tout service peut le vérifier.
 */
final class JwtService
{
    /** @return array{0: string, 1: CarbonImmutable} le jeton signé et sa date d'expiration */
    public function issue(string $username, string $role): array
    {
        $expiresAt = CarbonImmutable::now('UTC')->addSeconds((int) config('jwt.ttl_seconds'))->startOfSecond();

        $claims = [
            'iss'    => config('jwt.issuer'),   // qui a émis le jeton
            'upn'    => $username,              // identité
            'groups' => [$role],                // rôles
            'exp'    => $expiresAt->getTimestamp(),   // après cette date, le jeton est refusé
        ];

        return [JWT::encode($claims, $this->key('private'), 'RS256'), $expiresAt];
    }

    /**
     * Vérifie la signature et l'expiration, puis l'émetteur. Lève une exception si le jeton est invalide.
     */
    public function verify(string $token): stdClass
    {
        $claims = JWT::decode($token, new Key($this->key('public'), 'RS256'));   // algorithme imposé : jamais celui du client

        if (($claims->iss ?? null) !== config('jwt.issuer')) {
            throw new RuntimeException('Wrong issuer');
        }

        return $claims;
    }

    private function key(string $type): string
    {
        return config("jwt.{$type}_key") ?: (string) file_get_contents(config("jwt.{$type}_key_path"));
    }
}
