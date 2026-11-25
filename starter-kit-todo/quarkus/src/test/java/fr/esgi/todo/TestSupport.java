package fr.esgi.todo;

import static io.restassured.RestAssured.given;

import io.restassured.http.ContentType;
import io.restassured.specification.RequestSpecification;
import java.util.Map;

/** Fonctions d'aide des tests. L'application est partagée par tous les tests : chacun crée ses propres données. */
public final class TestSupport {

    public static final String ADMIN_USERNAME = "admin";
    public static final String ADMIN_PASSWORD = "Admin-Test-Password-1";

    private TestSupport() {
    }

    public static RequestSpecification anonymous() {
        return given().contentType(ContentType.JSON).accept(ContentType.JSON);
    }

    public static RequestSpecification as(String token) {
        return anonymous().header("Authorization", "Bearer " + token);
    }

    /** Obtient un jeton en appelant la vraie route de login. */
    public static String login(String username, String password) {
        return anonymous().body(Map.of("username", username, "password", password))
                .post("/api/v1/auth/login")
                .then().statusCode(200)
                .extract().path("token");
    }
}
