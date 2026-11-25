package fr.esgi.todo.api.dto;

import java.time.Instant;

/** Réponse du login : { "token", "token_type", "expires_at" } (snake_case configuré globalement). */
public record TokenResponse(String token, String tokenType, Instant expiresAt) {
}
