<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * EXEMPLE d'une route PROTÉGÉE : le middleware "jwt" (voir routes/api.php) exige un jeton valide.
 *   - sans jeton, ou avec un jeton invalide ou expiré : 401, et ce code n'est pas exécuté ;
 *   - avec un jeton valide : 200 et l'identité lue DANS le jeton.
 *
 * Projet : c'est ce middleware qu'on ajoutera aux routes de TodoController, puis "role:admin"
 * pour les actions réservées aux administrateurs.
 */
class MeController extends Controller
{
    #[OA\Get(
        path: '/api/v1/me',
        summary: 'Utilisateur authentifié, lu dans le jeton',
        security: [['bearerAuth' => []]],
        tags: ['Authentification'],
        responses: [
            new OA\Response(response: 200, description: 'Identité et rôles du jeton'),
            new OA\Response(response: 401, description: 'Jeton absent, invalide ou expiré', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $claims = $request->attributes->get('claims');   // posé par le middleware "jwt"

        return response()->json(['data' => ['username' => $claims->upn, 'roles' => $claims->groups]]);
    }
}
