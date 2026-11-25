<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Description générale de l'API et schémas communs, lus par L5-Swagger pour produire le contrat OpenAPI
 * et la page Swagger UI (http://localhost:8080/api/documentation).
 */
#[OA\Info(
    version: '1.0.0',
    title: 'API Todo',
    description: 'API de gestion de tâches du TP 3 (ESGI, API REST pour l\'administration système).',
)]
#[OA\Server(url: 'http://localhost:8080', description: 'Développement local')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Jeton obtenu par POST /api/v1/auth/login, envoyé dans l\'en-tête Authorization: Bearer <jeton>',
)]
#[OA\Schema(
    schema: 'Error',
    required: ['message'],
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Todo not found.')],
)]
#[OA\Schema(
    schema: 'ValidationError',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['title' => ['The title field is required.']],
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
)]
#[OA\Schema(
    schema: 'LoginRequest',
    required: ['username', 'password'],
    properties: [
        new OA\Property(property: 'username', type: 'string', example: 'admin'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
    ],
)]
#[OA\Schema(
    schema: 'TokenResponse',
    required: ['token', 'token_type', 'expires_at'],
    properties: [
        new OA\Property(property: 'token', type: 'string'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'TodoInput',
    required: ['title'],
    properties: [new OA\Property(property: 'title', type: 'string', minLength: 1, maxLength: 100, example: 'Sauvegarder la base')],
)]
#[OA\Schema(
    schema: 'Todo',
    required: ['id', 'title', 'done', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Sauvegarder la base'),
        new OA\Property(property: 'done', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'TodoEnvelope',
    required: ['data'],
    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Todo')],
)]
class Spec
{
}
