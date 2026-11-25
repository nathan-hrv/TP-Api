<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\JwtService;
use App\Services\UserDirectory;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Obtention d'un jeton : route PUBLIQUE (on ne peut pas exiger un jeton pour en obtenir un).
 * Le client envoie ses identifiants, le serveur répond par un JWT signé.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly UserDirectory $users,
        private readonly JwtService $jwt,
    ) {
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Obtenir un jeton d\'accès (JWT)',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
        tags: ['Authentification'],
        responses: [
            new OA\Response(response: 200, description: 'Jeton émis', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
            new OA\Response(response: 401, description: 'Identifiants invalides', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Données invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $role = $this->users->authenticate($credentials['username'], $credentials['password'])
            // Même message que le compte soit inconnu ou le mot de passe faux : pas d'énumération des comptes
            ?? throw new ApiException(401, 'Invalid credentials.');

        [$token, $expiresAt] = $this->jwt->issue($credentials['username'], $role);

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->format('Y-m-d\TH:i:s\Z'),
        ]);
    }
}
