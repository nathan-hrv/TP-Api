<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TodoStore;

/**
 * Ressource "todos".
 *
 * TP 3, étape 4 : écrire les trois méthodes (voir le cours, section 3.3).
 *   store  : POST /api/v1/todos        créer une tâche      (201 + Location, 422 si le titre est invalide)
 *   index  : GET  /api/v1/todos        lister les tâches    (200)
 *   show   : GET  /api/v1/todos/{id}   lire une tâche       (200, 404 si inconnue)
 *
 * Les erreurs (404, 422...) sont mises en forme par bootstrap/app.php : il suffit de lever une ApiException
 * ou de laisser la validation (FormRequest) échouer.
 */
class TodoController extends Controller
{
    public function __construct(private readonly TodoStore $store)
    {
    }
}
