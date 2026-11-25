<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Le fichier est servi sous le préfixe /api ; la version (/v1) est ajoutée dans routes/api.php
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt'  => AuthenticateJwt::class,   // exige un jeton JWT valide (401)
            'role' => EnsureRole::class,        // role:admin (403)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * GESTION CENTRALISÉE DES ERREURS (fournie : voir le cours, section 3.4).
         *
         * Une route ne fabrique jamais une réponse d'erreur à la main : elle lève une exception, et c'est ICI,
         * en un seul endroit, qu'elle devient une réponse JSON uniforme { "message": "..." }.
         *
         * ApiException(status, message)  -> status choisi par le code (404, 409...)
         * ValidationException            -> 422 + { message, errors } (géré par Laravel)
         * autre HttpException            -> son code (401, 403, 404, 405...) avec un message générique
         * toute autre exception (un bug) -> 500 SANS détail ; Laravel écrit la trace dans les journaux
         */
        $exceptions->shouldRenderJsonWhen(fn () => true);   // API uniquement : toujours du JSON

        $exceptions->render(function (Throwable $e) {
            if ($e instanceof ValidationException) {
                return null;   // 422 avec le détail par champ : le rendu par défaut convient
            }

            if ($e instanceof ApiException) {
                return response()->json(['message' => $e->getMessage()], $e->getStatusCode(), $e->getHeaders());
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $status === 404 ? 'Resource not found.' : (Response::$statusTexts[$status] ?? 'Error.');

                return response()->json(['message' => $message], $status, $e->getHeaders());
            }

            return response()->json(['message' => 'Server Error.'], 500);   // aucun détail pour le client
        });
    })
    ->create();
