package fr.esgi.todo.api.dto;

/**
 * Enveloppe des réponses de succès : { "data": ... }.
 * Elle permet d'ajouter plus tard des métadonnées à côté (pagination, liens) sans casser les clients.
 */
public record Envelope<T>(T data) {
}
