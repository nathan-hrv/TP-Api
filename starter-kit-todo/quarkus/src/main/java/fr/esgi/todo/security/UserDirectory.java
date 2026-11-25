package fr.esgi.todo.security;

import jakarta.enterprise.context.ApplicationScoped;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.Optional;
import org.eclipse.microprofile.config.inject.ConfigProperty;

/**
 * Annuaire des comptes de démonstration : deux comptes, dont les mots de passe viennent de l'environnement (.env).
 *
 * Simplification de TP : dans une vraie application, les comptes sont en base, avec des mots de passe HACHÉS
 * (bcrypt, argon2) : on ne compare jamais un mot de passe en clair.
 */
@ApplicationScoped
public class UserDirectory {

    private static final String DUMMY = "dummy-value-so-that-comparison-always-happens";

    @ConfigProperty(name = "todo.users.admin.password")
    Optional<String> adminPassword;

    @ConfigProperty(name = "todo.users.user.password")
    Optional<String> userPassword;

    /** Renvoie le rôle de l'utilisateur si les identifiants sont valides, sinon rien. */
    public Optional<String> authenticate(String username, String password) {
        Optional<String> expected;
        String role;
        switch (username) {
            case "admin" -> { expected = adminPassword; role = Roles.ADMIN; }
            case "user" -> { expected = userPassword; role = Roles.USER; }
            default -> { expected = Optional.empty(); role = null; }
        }

        boolean configured = expected.filter(p -> !p.isBlank()).isPresent();
        // Comparaison faite même pour un compte inconnu, et en temps constant : le temps de réponse ne trahit rien
        boolean same = MessageDigest.isEqual(
                expected.filter(p -> !p.isBlank()).orElse(DUMMY).getBytes(StandardCharsets.UTF_8),
                password.getBytes(StandardCharsets.UTF_8));

        return configured && same ? Optional.of(role) : Optional.empty();
    }
}
