<?php

namespace App\Services;

use App\Models\Todo;
use LogicException;

/**
 * Stockage des tâches EN MÉMOIRE : aucune base de données.
 *
 * Particularité de PHP (voir le cours, section 3.3) : PHP OUBLIE TOUT entre deux requêtes. Une simple
 * propriété ou un tableau statique seraient vidés à chaque appel. Pour garder un état, on utilise APCu, une
 * mémoire PARTAGÉE qui survit aux requêtes tant que le processus tourne :
 *   apcu_store($cle, $valeur)    écrire        apcu_fetch($cle, $ok)   lire ($ok à false si absent)
 *   apcu_add($cle, $valeur)      écrire seulement si la clé n'existe pas
 *   apcu_inc($cle)               incrémenter de façon ATOMIQUE (équivalent de AtomicInteger en Java)
 *
 * Conséquences : tout est perdu à l'arrêt, une seule instance de l'API peut tourner, et les compteurs
 * doivent être atomiques puisque plusieurs requêtes peuvent arriver en même temps.
 *
 * TP 3, étape 3 : compléter les trois méthodes. Tant qu'elles ne sont pas écrites, l'API répond 500.
 */
final class TodoStore
{
    // TP 3, étape 3 : déclarer ici les clés APCu utilisées, par exemple
    //   private const SEQUENCE = 'todos:seq';        // le compteur des identifiants
    //   chaque tâche étant rangée sous la clé "todos:{id}".

    /** Crée une tâche (id attribué par le serveur, done à false, createdAt à l'instant présent, en UTC). */
    public function create(string $title): Todo
    {
        throw new LogicException('À écrire (TP 3, étape 3)');
    }

    /** @return Todo[] Toutes les tâches, dans l'ordre de leur identifiant. */
    public function findAll(): array
    {
        throw new LogicException('À écrire (TP 3, étape 3)');
    }

    /** Une tâche, ou null si l'identifiant est inconnu. */
    public function findById(int $id): ?Todo
    {
        throw new LogicException('À écrire (TP 3, étape 3)');
    }
}
