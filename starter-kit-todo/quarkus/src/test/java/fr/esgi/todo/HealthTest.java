package fr.esgi.todo;

import static fr.esgi.todo.TestSupport.anonymous;
import static org.hamcrest.Matchers.equalTo;

import io.quarkus.test.junit.QuarkusTest;
import org.junit.jupiter.api.Test;

/** Test du socle fourni : la route publique répond sans jeton. Vert dès le départ. */
@QuarkusTest
class HealthTest {

    @Test
    void healthIsPublic() {
        anonymous().get("/api/v1/health")
                .then().statusCode(200)
                .body("status", equalTo("ok"));
    }
}
