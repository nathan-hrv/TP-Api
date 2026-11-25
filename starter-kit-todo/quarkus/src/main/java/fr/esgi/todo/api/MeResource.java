package fr.esgi.todo.api;

import fr.esgi.todo.api.dto.Envelope;
import io.quarkus.security.Authenticated;
import jakarta.inject.Inject;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import java.util.Map;
import org.eclipse.microprofile.jwt.JsonWebToken;
import org.eclipse.microprofile.openapi.annotations.Operation;
import org.eclipse.microprofile.openapi.annotations.responses.APIResponse;
import org.eclipse.microprofile.openapi.annotations.tags.Tag;

/**
 * EXEMPLE d'une route PROTÉGÉE : un jeton JWT valide est exigé (@Authenticated).
 *   - sans jeton, ou avec un jeton invalide ou expiré : 401 ;
 *   - avec un jeton valide : 200 et l'identité lue DANS le jeton.
 *
 * Projet : c'est cette annotation qu'on ajoutera aux routes de TodoResource, puis @RolesAllowed("admin")
 * pour les actions réservées aux administrateurs.
 */
@Path("/api/v1/me")
@Produces(MediaType.APPLICATION_JSON)
@Tag(name = "Authentification")
public class MeResource {

    @Inject
    JsonWebToken jwt;

    @GET
    @Authenticated
    @Operation(summary = "Utilisateur authentifié, lu dans le jeton")
    @APIResponse(responseCode = "200", description = "Identité et rôles du jeton")
    @APIResponse(responseCode = "401", description = "Jeton absent, invalide ou expiré")
    public Envelope<Map<String, Object>> me() {
        return new Envelope<>(Map.of("username", jwt.getName(), "roles", jwt.getGroups()));
    }
}
