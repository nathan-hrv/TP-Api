<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorise la requête si le jeton porte l'un des rôles listés : 403 sinon.
 * À placer APRÈS le middleware "jwt". Usage dans les routes : ->middleware(['jwt', 'role:admin'])
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $groups = (array) ($request->attributes->get('claims')->groups ?? []);

        if (array_intersect($roles, $groups) === []) {
            throw new ApiException(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
