package fr.esgi.todo;

import static fr.esgi.todo.TestSupport.ADMIN_PASSWORD;
import static fr.esgi.todo.TestSupport.ADMIN_USERNAME;
import static fr.esgi.todo.TestSupport.anonymous;
import static fr.esgi.todo.TestSupport.as;
import static fr.esgi.todo.TestSupport.login;
import static org.hamcrest.Matchers.contains;
import static org.hamcrest.Matchers.equalTo;
import static org.hamcrest.Matchers.hasKey;
import static org.hamcrest.Matchers.notNullValue;

import io.quarkus.test.junit.QuarkusTest;
import java.util.Map;
import org.junit.jupiter.api.Test;

/** Tests du socle fourni : login, route protégée et route publique. Verts dès le départ. */
@QuarkusTest
class AuthTest {

    @Test
    void loginReturnsBearerToken() {
        anonymous().body(Map.of("username", ADMIN_USERNAME, "password", ADMIN_PASSWORD))
                .post("/api/v1/auth/login")
                .then().statusCode(200)
                .body("token", notNullValue())
                .body("token_type", equalTo("Bearer"))
                .body("expires_at", notNullValue());
    }

    @Test
    void sameMessageForUnknownUserAndWrongPassword() {
        anonymous().body(Map.of("username", ADMIN_USERNAME, "password", "faux"))
                .post("/api/v1/auth/login")
                .then().statusCode(401).body("message", equalTo("Invalid credentials."));

        anonymous().body(Map.of("username", "inconnu", "password", "faux"))
                .post("/api/v1/auth/login")
                .then().statusCode(401).body("message", equalTo("Invalid credentials."));
    }

    @Test
    void loginBodyIsValidated() {
        anonymous().body(Map.of("username", ""))
                .post("/api/v1/auth/login")
                .then().statusCode(422)
                .body("errors", hasKey("username"))
                .body("errors", hasKey("password"));
    }

    @Test
    void protectedRouteRequiresToken() {
        anonymous().get("/api/v1/me")
                .then().statusCode(401)
                .header("WWW-Authenticate", "Bearer")
                .body("message", equalTo("Unauthenticated."));
    }

    @Test
    void protectedRouteRejectsInvalidToken() {
        as("pas-un-jwt").get("/api/v1/me").then().statusCode(401);
    }

    @Test
    void protectedRouteReturnsIdentityReadFromToken() {
        String token = login(ADMIN_USERNAME, ADMIN_PASSWORD);

        as(token).get("/api/v1/me")
                .then().statusCode(200)
                .body("data.username", equalTo("admin"))
                .body("data.roles", contains("admin"));
    }
}
