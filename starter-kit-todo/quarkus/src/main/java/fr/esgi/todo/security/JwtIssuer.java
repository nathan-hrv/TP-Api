package fr.esgi.todo.security;

import fr.esgi.todo.api.dto.TokenResponse;
import io.smallrye.jwt.build.Jwt;
import jakarta.enterprise.context.ApplicationScoped;
import java.time.Duration;
import java.time.Instant;
import java.time.temporal.ChronoUnit;
import java.util.Set;
import org.eclipse.microprofile.config.inject.ConfigProperty;

/**
 * Émet des JWT signés avec la clé PRIVÉE RSA (RS256). La vérification, à chaque requête, n'utilise que
 * la clé PUBLIQUE : seul ce service peut fabriquer un jeton, tout service peut le vérifier.
 */
@ApplicationScoped
public class JwtIssuer {

    @ConfigProperty(name = "mp.jwt.verify.issuer")
    String issuer;

    @ConfigProperty(name = "todo.token-ttl")
    Duration ttl;

    public TokenResponse issue(String username, String role) {
        Instant expiresAt = Instant.now().plus(ttl).truncatedTo(ChronoUnit.SECONDS);
        String token = Jwt.issuer(issuer)          // "iss" : qui a émis le jeton
                .upn(username)                      // identité, relue par jwt.getName()
                .groups(Set.of(role))               // rôles, lus par @RolesAllowed
                .expiresAt(expiresAt)               // "exp" : après cette date, le jeton est refusé
                .sign();                            // signature avec smallrye.jwt.sign.key.location
        return new TokenResponse(token, "Bearer", expiresAt);
    }
}
