package fr.esgi.todo.api.dto;

/** Format de toutes les erreurs : { "message": "..." }. */
public record ErrorBody(String message) {
}
