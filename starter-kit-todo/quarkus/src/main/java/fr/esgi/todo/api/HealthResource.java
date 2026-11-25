package fr.esgi.todo.api;

import jakarta.annotation.security.PermitAll;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import java.util.Map;
import org.eclipse.microprofile.openapi.annotations.Operation;
import org.eclipse.microprofile.openapi.annotations.responses.APIResponse;
import org.eclipse.microprofile.openapi.annotations.tags.Tag;

/**
 * EXEMPLE d'une route PUBLIQUE : aucun jeton n'est demandé (@PermitAll).
 * C'est la route qu'une sonde de supervision ou un orchestrateur appelle pour savoir si le service répond.
 * Elle est volontairement pauvre en informations : elle dit si le service fonctionne, rien de plus.
 */
@Path("/api/v1/health")
@Produces(MediaType.APPLICATION_JSON)
@Tag(name = "Exploitation")
public class HealthResource {

    @GET
    @PermitAll
    @Operation(summary = "État du service")
    @APIResponse(responseCode = "200", description = "Service opérationnel")
    public Map<String, String> health() {
        return Map.of("status", "ok");
    }
}
