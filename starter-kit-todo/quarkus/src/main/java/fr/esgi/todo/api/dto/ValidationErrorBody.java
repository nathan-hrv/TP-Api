package fr.esgi.todo.api.dto;

import java.util.List;
import java.util.Map;

/** Erreur de validation (422) : { "message": "...", "errors": { "champ": ["message"] } }. */
public record ValidationErrorBody(String message, Map<String, List<String>> errors) {

    public ValidationErrorBody(Map<String, List<String>> errors) {
        this("The given data was invalid.", errors);
    }
}
