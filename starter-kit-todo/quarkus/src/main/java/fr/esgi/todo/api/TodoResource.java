package fr.esgi.todo.api;

import fr.esgi.todo.store.TodoStore;
import jakarta.inject.Inject;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import org.eclipse.microprofile.openapi.annotations.tags.Tag;

/**
 * Ressource "todos".
 *
 * TP 3, étape 4 : écrire les trois routes (voir le cours, section 3.3).
 *   POST /api/v1/todos        créer une tâche      (201 + Location, 422 si le titre est invalide)
 *   GET  /api/v1/todos        lister les tâches    (200)
 *   GET  /api/v1/todos/{id}   lire une tâche       (200, 404 si inconnue)
 *
 * Les erreurs (404, 422...) sont mises en forme par api/error/ExceptionMappers : il suffit de lever
 * une ApiException ou de laisser la validation échouer.
 */
@Path("/api/v1/todos")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
@Tag(name = "Todos")
public class TodoResource {

    @Inject
    TodoStore store;

}
