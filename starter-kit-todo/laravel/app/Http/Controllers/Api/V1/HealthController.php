<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * EXEMPLE d'une route PUBLIQUE : aucun middleware d'authentification, aucun jeton demandé.
 * C'est la route qu'une sonde de supervision ou un orchestrateur appelle pour savoir si le service répond.
 * Elle est volontairement pauvre en informations : elle dit si le service fonctionne, rien de plus.
 */
class HealthController extends Controller
{
    #[OA\Get(
        path: '/api/v1/health',
        summary: 'État du service',
        tags: ['Exploitation'],
        responses: [new OA\Response(response: 200, description: 'Service opérationnel')],
    )]
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
