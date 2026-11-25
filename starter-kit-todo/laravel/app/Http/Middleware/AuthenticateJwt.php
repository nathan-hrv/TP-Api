<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exige un jeton JWT valide : 401 sinon. Les claims sont rangés dans la requête (attribut "claims").
 * Usage dans les routes : ->middleware('jwt')
 */
class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new ApiException(401, 'Unauthenticated.');
        }

        try {
            $claims = $this->jwt->verify($token);
        } catch (Throwable) {
            throw new ApiException(401, 'Unauthenticated.');   // absent, falsifié, expiré, mauvais émetteur
        }

        $request->attributes->set('claims', $claims);

        return $next($request);
    }
}
