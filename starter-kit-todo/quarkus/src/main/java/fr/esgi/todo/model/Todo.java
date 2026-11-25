package fr.esgi.todo.model;

import java.time.Instant;

/**
 * Une tâche. Modèle fourni : c'est ce que l'API expose.
 *
 * @param id        identifiant entier, attribué par le serveur (incrémental, à partir de 1)
 * @param title     intitulé de la tâche (100 caractères au plus)
 * @param done      vrai quand la tâche est terminée ; faux à la création
 * @param createdAt date de création, au format ISO 8601 dans le JSON ("created_at")
 */
public record Todo(int id, String title, boolean done, Instant createdAt) {
}
