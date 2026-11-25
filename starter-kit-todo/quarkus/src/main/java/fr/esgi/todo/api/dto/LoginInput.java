package fr.esgi.todo.api.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

/** Corps de POST /auth/login. Exemple de DTO d'entrée validé par Bean Validation. */
public record LoginInput(
        @NotBlank @Size(max = 50) String username,
        @NotBlank @Size(max = 255) String password) {
}
