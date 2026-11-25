package fr.esgi.todo.api;

import fr.esgi.todo.api.dto.LoginInput;
import fr.esgi.todo.api.dto.TokenResponse;
import fr.esgi.todo.api.error.ApiException;
import fr.esgi.todo.security.JwtIssuer;
import fr.esgi.todo.security.UserDirectory;
import jakarta.annotation.security.PermitAll;
import jakarta.inject.Inject;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotNull;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import org.eclipse.microprofile.openapi.annotations.Operation;
import org.eclipse.microprofile.openapi.annotations.responses.APIResponse;
import org.eclipse.microprofile.openapi.annotations.tags.Tag;

/**
 * Obtention d'un jeton : route PUBLIQUE (on ne peut pas exiger un jeton pour en obtenir un).
 * Le client envoie ses identifiants, le serveur répond par un JWT signé.
 */
@Path("/api/v1/auth")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
@Tag(name = "Authentification")
public class AuthResource {

    @Inject
    UserDirectory users;

    @Inject
    JwtIssuer issuer;

    @POST
    @Path("/login")
    @PermitAll
    @Operation(summary = "Obtenir un jeton d'accès (JWT)")
    @APIResponse(responseCode = "200", description = "Jeton émis")
    @APIResponse(responseCode = "401", description = "Identifiants invalides")
    @APIResponse(responseCode = "422", description = "Données invalides")
    public TokenResponse login(@Valid @NotNull LoginInput input) {
        String role = users.authenticate(input.username(), input.password())
                // Même message que le compte soit inconnu ou le mot de passe faux : pas d'énumération des comptes
                .orElseThrow(() -> new ApiException(401, "Invalid credentials."));
        return issuer.issue(input.username(), role);
    }
}
