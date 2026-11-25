# Starter kit Todo (Quarkus) : API de gestion de tâches

Squelette du TP 3 (chapitre 3, version 2) : une API REST de tâches, **sans base de données** (stockage en mémoire).
Quarkus 3, Java 21, SmallRye JWT (RS256), SmallRye OpenAPI et Swagger UI. Aucun Java ni Maven n'est nécessaire sur votre poste : tout s'exécute dans Docker.

## Démarrer

```bash
cp .env.example .env
# Renseigner APP_ADMIN_PASSWORD et APP_USER_PASSWORD (mots de passe de votre choix)

docker compose up -d
docker compose logs -f api        # premier démarrage : téléchargement Maven (plusieurs minutes),
                                  # attendre "Listening on: http://0.0.0.0:8080"

curl -s http://localhost:8080/api/v1/health
```

| URL | Rôle |
| --- | --- |
| `http://localhost:8080/api/v1/health` | Route publique (exemple fourni) |
| `http://localhost:8080/api/docs` | Documentation interactive Swagger UI |
| `http://localhost:8080/api/openapi` | Contrat OpenAPI brut |

L'API tourne en **mode développement** : toute modification d'un fichier Java est prise en compte à la requête suivante.

## Ce que fournit le kit

| Élément | Fichier (sous `src/main/java/fr/esgi/todo/`) |
| --- | --- |
| Modèle `Todo` (id entier, title, done, created_at) | `model/Todo.java` |
| Route publique d'exemple : `GET /health` | `api/HealthResource.java` |
| Route protégée d'exemple : `GET /me` (jeton JWT exigé) | `api/MeResource.java` |
| Obtention d'un jeton : `POST /auth/login` | `api/AuthResource.java`, `security/` |
| Émission des JWT (RS256), comptes de démonstration | `security/JwtIssuer.java`, `security/UserDirectory.java` |
| Gestion centralisée des erreurs (404, 422, 401, 500...) | `api/error/ExceptionMappers.java` |
| Enveloppes et formats d'erreur | `api/dto/` |
| Tests du socle (verts) | `src/test/java/fr/esgi/todo/HealthTest.java`, `AuthTest.java` |
| Tests du TP 3 (rouges au départ) | `src/test/java/fr/esgi/todo/TodoApiTest.java` |
| Collection Postman et environnement | `postman/` |

## Ce que vous écrivez (TP 3)

| Étape | Fichier |
| --- | --- |
| Le stockage en mémoire | `store/TodoStore.java` (squelette à compléter) |
| Le DTO d'entrée validé | `api/dto/TodoInput.java` (à créer) |
| Les trois routes | `api/TodoResource.java` (squelette à compléter) |

Ne modifiez pas `TodoApiTest` : c'est le contrat.

## Commandes utiles

```bash
docker compose run --rm tests                                     # tous les tests
docker compose run --rm tests mvn -B test -Dtest=TodoApiTest      # le TP 3 seulement
docker compose logs -f api                                        # journaux
docker compose restart api                                        # redémarrage (les tâches en mémoire sont perdues)
```

## Obtenir un jeton à la main

```bash
curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"<APP_ADMIN_PASSWORD>"}'
curl -s http://localhost:8080/api/v1/me -H "Authorization: Bearer <jeton>"
```

Les comptes sont `admin` (rôle `admin`) et `user` (rôle `user`).
