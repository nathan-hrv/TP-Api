package fr.esgi.todo.api.error;

/**
 * Erreur de validation sur un champ, détectée par le code plutôt que par une annotation
 * (par exemple un PATCH sans aucun champ). Rendue en 422 avec le même format que Bean Validation.
 * Usage : throw new FieldValidationException("body", "At least one field is required.");
 */
public class FieldValidationException extends RuntimeException {

    private final String field;

    public FieldValidationException(String field, String message) {
        super(message);
        this.field = field;
    }

    public String field() {
        return field;
    }
}
