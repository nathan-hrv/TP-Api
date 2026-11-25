<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
 * Matrice des routes et des droits. Une revue de sécurité commence ici :
 *   php artisan route:list --path=api
 *
 * Toutes les routes sont servies sous /api (bootstrap/app.php) puis /v1 (ci-dessous).
 */
Route::prefix('v1')->group(function () {

    // Routes PUBLIQUES : aucun middleware, aucun jeton demandé
    Route::get('health', HealthController::class);
    Route::post('auth/login', [AuthController::class, 'login']);

    // Route PROTÉGÉE (exemple) : le middleware "jwt" répond 401 sans jeton valide
    Route::get('me', MeController::class)->middleware('jwt');

    // TP 3, étape 4 : déclarer ici les trois routes de TodoController
    //   POST todos, GET todos, GET todos/{id} (avec ->whereNumber('id'))
    // Projet : les placer dans Route::middleware('jwt')->group(...), puis ->middleware('role:admin') pour la suppression.
});
