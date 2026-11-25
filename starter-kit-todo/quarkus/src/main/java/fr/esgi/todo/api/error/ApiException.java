package fr.esgi.todo.api.error;

import java.util.Map;

/**
 * Erreur volontaire du code (404, 409...), rendue en JSON par ExceptionMappers.
 * Usage : throw new ApiException(404, "Todo not found.");
 */
public class ApiException extends RuntimeException {

    private final int status;
    private final Map<String, String> headers;

    public ApiException(int status, String message) {
        this(status, message, Map.of());
    }

    public ApiException(int status, String message, Map<String, String> headers) {
        super(message);
        this.status = status;
        this.headers = headers;
    }

    public int status() {
        return status;
    }

    public Map<String, String> headers() {
        return headers;
    }
}
