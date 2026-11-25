package fr.esgi.todo;

import static fr.esgi.todo.TestSupport.anonymous;
import static org.hamcrest.Matchers.endsWith;
import static org.hamcrest.Matchers.equalTo;
import static org.hamcrest.Matchers.greaterThan;
import static org.hamcrest.Matchers.hasItem;
import static org.hamcrest.Matchers.hasKey;
import static org.hamcrest.Matchers.instanceOf;
import static org.hamcrest.Matchers.is;
import static org.hamcrest.Matchers.not;
import static org.hamcrest.Matchers.notNullValue;
import static org.junit.jupiter.api.Assertions.assertTrue;

import io.quarkus.test.junit.QuarkusTest;
import io.restassured.response.Response;
import java.util.Map;
import org.junit.jupiter.api.Test;

/**
 * TP 3 : les trois routes de l'API Todo. Ces tests décrivent le contrat (cours, section 3.2).
 * Ils sont ROUGES au départ. Ne les modifiez pas : faites-les passer au vert.
 *
 *   docker compose run --rm tests mvn -B test -Dtest=TodoApiTest
 *
 * L'application est partagée par tous les tests : aucun ne suppose que la liste est vide.
 */
@QuarkusTest
class TodoApiTest {

    private static Response create(String title) {
        return anonymous().body(Map.of("title", title)).post("/api/v1/todos");
    }

    // ---------- POST /todos ----------

    @Test
    void createReturns201WithLocationAndTheNewTodo() {
        Response response = create("Sauvegarder la base");

        response.then().statusCode(201)
                .body("data.id", instanceOf(Integer.class))
                .body("data.id", greaterThan(0))
                .body("data.title", equalTo("Sauvegarder la base"))
                .body("data.done", is(false))
                .body("data.created_at", notNullValue());

        int id = response.path("data.id");
        response.then().header("Location", endsWith("/api/v1/todos/" + id));
    }

    @Test
    void identifiersIncrease() {
        int first = create("Première").path("data.id");
        int second = create("Seconde").path("data.id");

        assertTrue(second > first, "le second identifiant doit être supérieur au premier");
    }

    @Test
    void titleIsRequired() {
        anonymous().body(Map.of()).post("/api/v1/todos")
                .then().statusCode(422)
                .body("message", notNullValue())
                .body("errors", hasKey("title"));
    }

    @Test
    void blankTitleIsRefused() {
        create("").then().statusCode(422).body("errors", hasKey("title"));
        create("   ").then().statusCode(422).body("errors", hasKey("title"));
    }

    @Test
    void titleLongerThan100CharactersIsRefused() {
        create("x".repeat(100)).then().statusCode(201);
        create("x".repeat(101)).then().statusCode(422).body("errors", hasKey("title"));
    }

    @Test
    void clientCannotChooseTheStateOfANewTodo() {
        // Un champ non prévu par le contrat est ignoré : une tâche naît toujours non terminée
        anonymous().body(Map.of("title", "Tentative", "done", true, "id", 999999))
                .post("/api/v1/todos")
                .then().statusCode(201)
                .body("data.done", is(false))
                .body("data.id", not(999999));
    }

    // ---------- GET /todos ----------

    @Test
    void listContainsTheCreatedTodo() {
        int id = create("Vérifier les sauvegardes").path("data.id");

        anonymous().get("/api/v1/todos")
                .then().statusCode(200)
                .body("data.id", hasItem(id))
                .body("data.title", hasItem("Vérifier les sauvegardes"));
    }

    @Test
    void listIsOrderedByIdentifier() {
        create("A");
        create("B");

        java.util.List<Integer> ids = anonymous().get("/api/v1/todos").then().statusCode(200).extract().path("data.id");
        assertTrue(ids.equals(ids.stream().sorted().toList()), "la liste doit être triée par identifiant");
    }

    // ---------- GET /todos/{id} ----------

    @Test
    void showReturnsOneTodo() {
        int id = create("Mettre à jour le serveur").path("data.id");

        anonymous().get("/api/v1/todos/" + id)
                .then().statusCode(200)
                .body("data.id", is(id))
                .body("data.title", equalTo("Mettre à jour le serveur"))
                .body("data.done", is(false));
    }

    @Test
    void unknownIdentifierIs404WithAJsonMessage() {
        anonymous().get("/api/v1/todos/999999999")
                .then().statusCode(404)
                .body("$", hasKey("message"));
    }

    @Test
    void nonNumericIdentifierIs404() {
        anonymous().get("/api/v1/todos/abc").then().statusCode(404);
    }
}
