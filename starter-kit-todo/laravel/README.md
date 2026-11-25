# Starter kit Todo (Laravel) : API de gestion de tâches

Squelette du TP 3 (chapitre 3, version 2) : une API REST de tâches, **sans base de données** (stockage en mémoire).
Laravel 12, PHP 8.4, JWT RS256 (firebase/php-jwt), L5-Swagger (Swagger UI), Pest. Aucun PHP n'est nécessaire sur votre poste : tout s'exécute dans Docker.

## Démarrer

```bash
cp .env.example .env
# Renseigner APP_ADMIN_PASSWORD et APP_USER_PASSWORD (mots de passe de votre choix)

docker compose up -d --build
docker compose logs -f app        # premier démarrage : installation de Composer (quelques minutes),
                                  # attendre "Server running on [http://0.0.0.0:8080]"

curl -s http://localhost:8080/api/v1/health
```

| URL | Rôle |
| --- | --- |
| `http://localhost:8080/api/v1/health` | Route publique (exemple fourni) |
| `http://localhost:8080/api/documentation` | Documentation interactive Swagger UI (générée à partir des attributs du code) |

Le code est monté depuis votre poste : toute modification d'un fichier PHP est prise en compte à la requête suivante.

## Ce que fournit le kit

| Élément | Fichier |
| --- | --- |
| Modèle `Todo` (id entier, title, done, created_at) | `app/Models/Todo.php` |
| Route publique d'exemple : `GET /health` | `app/Http/Controllers/Api/V1/HealthController.php` |
| Route protégée d'exemple : `GET /me` (middleware `jwt`) | `app/Http/Controllers/Api/V1/MeController.php`, `routes/api.php` |
| Obtention d'un jeton : `POST /auth/login` (exemple de FormRequest) | `app/Http/Controllers/Api/V1/AuthController.php`, `app/Http/Requests/LoginRequest.php` |
| Émission et vérification des JWT (RS256), middlewares `jwt` et `role` | `app/Services/JwtService.php`, `app/Http/Middleware/` |
| Gestion centralisée des erreurs (404, 422, 401, 500...) | `bootstrap/app.php`, `app/Exceptions/ApiException.php` |
| Description OpenAPI et schémas | `app/OpenApi/Spec.php` |
| Tests du socle (verts) | `tests/Feature/HealthTest.php`, `AuthTest.php` |
| Tests du TP 3 (rouges au départ) | `tests/Feature/TodoApiTest.php` |
| Collection Postman et environnement | `postman/` |

## Ce que vous écrivez (TP 3)

| Étape | Fichier |
| --- | --- |
| Le stockage en mémoire (APCu) | `app/Services/TodoStore.php` (squelette à compléter) |
| La validation du corps de `POST /todos` | `app/Http/Requests/StoreTodoRequest.php` (à créer) |
| Les trois routes | `app/Http/Controllers/Api/V1/TodoController.php` (squelette) et `routes/api.php` |

Ne modifiez pas `TodoApiTest.php` : c'est le contrat.

## Commandes utiles

```bash
docker compose run --rm tests                                        # tous les tests
docker compose run --rm tests php artisan test --filter=TodoApiTest  # le TP 3 seulement
docker compose exec app php artisan route:list --path=api            # la liste des routes
docker compose exec app php artisan make:request StoreTodoRequest    # générer une FormRequest
docker compose logs -f app                                           # journaux
docker compose restart app                                           # redémarrage (les tâches en mémoire sont perdues)
```

## Obtenir un jeton à la main

```bash
curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"<APP_ADMIN_PASSWORD>"}'
curl -s http://localhost:8080/api/v1/me -H "Authorization: Bearer <jeton>"
```

Les comptes sont `admin` (rôle `admin`) et `user` (rôle `user`).

## Pourquoi APCu, et pourquoi un seul processus ?

PHP **oublie tout entre deux requêtes** : une variable ou un tableau statique sont vidés à chaque appel. Pour garder les tâches « en mémoire », le kit utilise **APCu**, une mémoire partagée qui survit aux requêtes tant que le processus tourne. Le serveur intégré (`php artisan serve`) est lancé avec `PHP_CLI_SERVER_WORKERS=1` : un seul processus, donc une seule mémoire. Avec plusieurs processus, chacun verrait sa propre liste.
