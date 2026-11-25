<?php

namespace App\Models;

/**
 * Une tâche. Modèle fourni : c'est ce que l'API expose.
 *
 * @param int    $id        identifiant entier, attribué par le serveur (incrémental, à partir de 1)
 * @param string $title     intitulé de la tâche (100 caractères au plus)
 * @param bool   $done      vrai quand la tâche est terminée ; faux à la création
 * @param string $createdAt date de création (UTC), au format ISO 8601
 */
final readonly class Todo
{
    public function __construct(
        public int $id,
        public string $title,
        public bool $done,
        public string $createdAt,
    ) {
    }

    /** Représentation exposée par l'API (le JSON est en snake_case : created_at). */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'done'       => $this->done,
            'created_at' => $this->createdAt,
        ];
    }
}
