package fr.esgi.todo.api.error;

import com.fasterxml.jackson.core.JsonProcessingException;
import fr.esgi.todo.api.dto.ErrorBody;
import fr.esgi.todo.api.dto.ValidationErrorBody;
import io.quarkus.hibernate.validator.runtime.jaxrs.ResteasyReactiveViolationException;
import io.quarkus.security.AuthenticationFailedException;
import io.quarkus.security.ForbiddenException;
import io.quarkus.security.UnauthorizedException;
import jakarta.validation.ConstraintViolation;
import jakarta.validation.ConstraintViolationException;
import jakarta.validation.Path;
import jakarta.ws.rs.NotFoundException;
import jakarta.ws.rs.WebApplicationException;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.TreeMap;
import java.util.stream.Collectors;
import org.jboss.logging.Logger;
import org.jboss.resteasy.reactive.server.ServerExceptionMapper;

/**
 * GESTION CENTRALISÉE DES ERREURS (fournie : voir le cours, section 3.4).
 *
 * Principe : une route ne fabrique jamais une réponse d'erreur à la main. Elle lève une exception, et c'est
 * UN SEUL endroit, ici, qui la transforme en réponse JSON uniforme { "message": "..." }.
 * Le client écrit ainsi un seul gestionnaire d'erreurs, et aucune trace Java ne fuit jamais vers lui.
 *
 * Exception levée                              -> Réponse
 * ApiException(status, message)                -> status choisi par le code (404, 409...)
 * ConstraintViolationException (@Valid...)     -> 422 + détail par champ
 * JSON mal formé                               -> 400
 * absence ou invalidité du jeton               -> 401 + WWW-Authenticate: Bearer
 * rôle insuffisant                             -> 403
 * route ou identifiant inconnu                 -> 404
 * toute autre exception (un bug)               -> 500 SANS détail ; la trace va dans les journaux
 */
public class ExceptionMappers {

    private static final Logger LOG = Logger.getLogger(ExceptionMappers.class);

    @ServerExceptionMapper
    public Response api(ApiException e) {
        Response.ResponseBuilder response = json(e.status(), new ErrorBody(e.getMessage()));
        e.headers().forEach(response::header);
        if (e.status() == 401) {
            response.header("WWW-Authenticate", "Bearer");   // exigé par la RFC 6750
        }
        return response.build();
    }

    // Erreur de validation détectée par le code (FieldValidationException) : même format que @Valid
    @ServerExceptionMapper
    public Response fieldValidation(FieldValidationException e) {
        return json(422, new ValidationErrorBody(Map.of(e.field(), List.of(e.getMessage())))).build();
    }

    // Par défaut, Quarkus répond 400 sur une violation de contrainte : le contrat exige 422
    @ServerExceptionMapper({ConstraintViolationException.class, ResteasyReactiveViolationException.class})
    public Response violations(ConstraintViolationException e) {
        Map<String, List<String>> errors = e.getConstraintViolations().stream()
                .collect(Collectors.groupingBy(
                        v -> field(v.getPropertyPath()),
                        TreeMap::new,
                        Collectors.mapping(ConstraintViolation::getMessage, Collectors.toList())));
        return json(422, new ValidationErrorBody(errors)).build();
    }

    // Corps JSON mal formé ou de type incompatible
    @ServerExceptionMapper
    public Response malformedJson(JsonProcessingException e) {
        return json(400, new ErrorBody("Malformed JSON.")).build();
    }

    @ServerExceptionMapper({UnauthorizedException.class, AuthenticationFailedException.class})
    public Response unauthenticated(RuntimeException e) {
        return json(401, new ErrorBody("Unauthenticated.")).header("WWW-Authenticate", "Bearer").build();
    }

    @ServerExceptionMapper
    public Response forbidden(ForbiddenException e) {
        return json(403, new ErrorBody("This action is unauthorized.")).build();
    }

    @ServerExceptionMapper
    public Response notFound(NotFoundException e) {
        return json(404, new ErrorBody("Resource not found.")).build();
    }

    @ServerExceptionMapper
    public Response webApplication(WebApplicationException e) {
        Response original = e.getResponse();
        return json(original.getStatus(), new ErrorBody(original.getStatusInfo().getReasonPhrase())).build();
    }

    // Tout le reste : 500 sans aucun détail pour le client, la trace va dans les journaux
    @ServerExceptionMapper
    public Response unexpected(Throwable e) {
        LOG.error("Unhandled error", e);
        return json(500, new ErrorBody("Server Error.")).build();
    }

    private static Response.ResponseBuilder json(int status, Object body) {
        return Response.status(status).type(MediaType.APPLICATION_JSON).entity(body);
    }

    /** Dernier élément du chemin de la violation, en snake_case : "create.input.title" donne "title". */
    private static String field(Path path) {
        String last = "";
        for (Path.Node node : path) {
            if (node.getName() != null) {
                last = node.getName();
            }
        }
        return last.replaceAll("([a-z0-9])([A-Z])", "$1_$2").toLowerCase(Locale.ROOT);
    }
}
