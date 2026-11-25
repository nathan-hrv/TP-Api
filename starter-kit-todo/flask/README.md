# API Todo (Flask) : version 1.0.0

API REST de gestion des tâches de maintenance d'une équipe d'exploitation : créer, lister, filtrer, marquer
terminée et supprimer une tâche. Elle est **sécurisée** (JWT et rôles), **documentée** (Swagger UI),
**testée** (pytest et Postman/Newman) et **publiée** sous la forme d'une image Docker versionnée.

Pile : Python 3.12, Flask 3, marshmallow, PyJWT (RS256), gunicorn. Stockage **en mémoire** (voir *Limites connues*).
Aucun Python n'est nécessaire sur le poste : tout s'exécute dans Docker.

| Document | Contenu |
| --- | --- |
| [`docs/openapi.yaml`](docs/openapi.yaml) | Contrat OpenAPI exporté de la version publiée |
| [`docs/schemas.md`](docs/schemas.md) | Schémas : étapes de sécurisation et chaîne de publication |
| [`postman/`](postman/) | Collection, environnements (sans secret) et rapports Newman |
| [`CHANGELOG.md`](CHANGELOG.md) | Historique des versions |

---

## 1. Démarrage depuis zéro

Prérequis : Docker Desktop (ou Docker Engine et Docker Compose v2), Git. Postman ou Node.js pour Newman (facultatif :
le script sait utiliser l'image Docker de Newman).

```bash
git clone <url-du-dépôt> && cd <dépôt>/starter-kit-todo/flask

cp .env.example .env
# Éditer .env : choisir APP_ADMIN_PASSWORD et APP_USER_PASSWORD (ce fichier n'est JAMAIS commité)

docker compose up -d --build          # génère les clés JWT (service keys) puis lance l'API
docker compose logs -f api            # attendre « Listening at: http://0.0.0.0:8080 »

curl -s http://localhost:8081/api/v1/health      # {"status":"ok"}
```

| URL (développement) | Rôle |
| --- | --- |
| `http://localhost:8081/api/v1` | Racine de l'API |
| `http://localhost:8081/api/docs` | Swagger UI (chargée depuis un CDN : accès Internet requis) |
| `http://localhost:8081/api/openapi.yaml` | Contrat OpenAPI brut |

Dans Swagger UI : exécuter `POST /auth/login`, copier le `token`, cliquer **Authorize** et coller le jeton (sans le
mot « Bearer ») : les routes protégées deviennent testables.

## 2. Variables d'environnement

| Variable | Obligatoire | Défaut | Rôle |
| --- | --- | --- | --- |
| `APP_ADMIN_PASSWORD` | oui | (vide : login impossible) | Mot de passe du compte `admin` |
| `APP_USER_PASSWORD` | oui | (vide : login impossible) | Mot de passe du compte `user` |
| `JWT_PRIVATE_KEY_PATH` | non | `/keys/privateKey.pem` | Clé privée RSA : signe les jetons |
| `JWT_PUBLIC_KEY_PATH` | non | `/keys/publicKey.pem` | Clé publique RSA : vérifie les jetons |
| `TOKEN_TTL_SECONDS` | non | `3600` | Durée de vie d'un jeton (courte : un JWT ne se révoque pas) |

Les deux mots de passe vivent dans `.env` (ignoré par Git et par Docker). Les clés RSA sont générées par le service
`keys` du docker-compose dans le volume `todo-flask_jwt-keys` : elles ne sont jamais dans le dépôt ni dans l'image.

## 3. Comptes de démonstration

| Compte | Rôle | Droits |
| --- | --- | --- |
| `admin` | admin | Tout, y compris **supprimer** une tâche |
| `user` | user | Lister, filtrer, lire, créer, modifier ; **pas** supprimer (403) |

Les mots de passe sont ceux que vous avez choisis dans `.env`.

```bash
TOKEN=$(curl -s -X POST http://localhost:8081/api/v1/auth/login -H 'Content-Type: application/json' \
  -d '{"username":"user","password":"<APP_USER_PASSWORD>"}' | python3 -c 'import sys,json; print(json.load(sys.stdin)["token"])')
curl -s http://localhost:8081/api/v1/todos -H "Authorization: Bearer $TOKEN"
```

## 4. Les routes

Toutes sous `/api/v1`. Réponses `{"data": ...}` ; erreurs `{"message": "..."}`, avec `errors` par champ pour les 422.

| Route | Accès | Succès | Erreurs |
| --- | --- | --- | --- |
| `GET /health` | public | 200 | |
| `POST /auth/login` | public | 200 + jeton | 401, 422 |
| `GET /me` | jeton | 200 | 401 |
| `GET /todos` (`?done=true` ou `?done=false`) | jeton | 200, trié par id | 401, 422 (filtre invalide) |
| `POST /todos` | jeton | 201 + `Location` | 401, 422 |
| `GET /todos/{id}` | jeton | 200 | 401, 404 |
| `PATCH /todos/{id}` (`title`, `done` ou les deux) | jeton | 200 + la tâche | 401, 404, 422 (`{}`, titre invalide, `done` non booléen) |
| `DELETE /todos/{id}` | **admin** | 204 sans corps | 401, 403, 404 |

## 5. Lancer les tests automatisés

```bash
docker compose run --rm tests                                   # toute la suite (pytest)
docker compose run --rm tests pytest tests/test_todos.py        # les tests du TP 3 (adaptés : jeton ajouté)
docker compose run --rm tests pytest --junitxml=results.xml     # rapport JUnit
```

| Fichier | Ce qu'il vérifie |
| --- | --- |
| `tests/test_store.py` | Test **unitaire** du stockage, sans HTTP : 100 créations simultanées, 100 id distincts, aucune perte |
| `tests/test_todos.py` | Tests du TP 3, inchangés sauf l'envoi d'un jeton valide |
| `tests/test_todos_project.py` | PATCH (200, 404, `{}` 422, titre invalide 422), DELETE (204 puis 404), filtre (`true`, `false`, `maybe` 422) |
| `tests/test_security.py` | 401 sans jeton sur toutes les routes todos, jeton falsifié 401, DELETE avec le rôle user 403 |
| `tests/test_docs.py`, `tests/test_docs_accuracy.py` | La documentation couvre toutes les routes et ne ment pas sur leur protection |
| `tests/test_auth.py`, `tests/test_health.py` | Socle fourni : login, `/me`, `/health` |

## 6. Lancer la collection Postman avec Newman

```bash
scripts/newman.sh local     # contre docker compose (port 8081), deux exécutions de suite
```

Le script lit les mots de passe dans `.env` et les passe en `--env-var` : ils ne sont écrits dans aucun fichier.
Rapports JUnit dans `postman/reports/`. Équivalent manuel :

```bash
npx newman run postman/todo-api.postman_collection.json -e postman/todo-local.postman_environment.json \
  --env-var admin_password=... --env-var user_password=...
```

Dans Postman : importer la collection et un environnement, saisir les mots de passe en **valeur courante**
(jamais en valeur initiale, qui part dans les exports), puis lancer le Runner.

La collection contient : **1. Authentification** (login admin et user, jetons rangés dans des variables),
**2. Scénario rejouable** (créer, lire, PATCH, filtrer, supprimer en admin, relire en 404), **3. Cas d'erreur**
(401, 403, 404, 422). Chaque requête a ses tests (statut, en-têtes, structure), dont 20 validations par JSON Schema.
Le scénario crée et supprime ses propres données, et la dernière requête efface les jetons des variables.

## 7. Image de production et publication

```bash
scripts/publish.sh 1.0.0      # build (cible prod), vérifications, registre local, push, preuve curl
scripts/run-prod.sh 1.0.0     # lance l'image HORS docker-compose sur http://localhost:8080
scripts/newman.sh image       # rejoue la collection contre l'image : preuve qu'elle est autonome
git tag -a v1.0.0 -m "Version 1.0.0" && git push origin v1.0.0
```

Ce que font les scripts, en commandes brutes :

```bash
docker build --target prod -t todo-api:1.0.0 .
docker run --rm -p 127.0.0.1:8080:8080 --env-file .env \
  -e JWT_PUBLIC_KEY_PATH=/keys/publicKey.pem -e JWT_PRIVATE_KEY_PATH=/keys/privateKey.pem \
  -v todo-flask_jwt-keys:/keys:ro todo-api:1.0.0

docker run -d --name registry -p 127.0.0.1:5000:5000 registry:2
docker tag todo-api:1.0.0 localhost:5000/todo-api:1.0.0
docker push localhost:5000/todo-api:1.0.0
curl -s http://localhost:5000/v2/_catalog              # {"repositories":["todo-api"]}
curl -s http://localhost:5000/v2/todo-api/tags/list    # {"name":"todo-api","tags":["1.0.0","latest"]}
```

L'image de production :
- est construite **en plusieurs étapes** (`base`, `dev`, `prod`) : ni tests ni outils de développement dedans ;
- tourne sous l'utilisateur **`app`** (non root), et le code appartient à root (lecture seule pour le processus) ;
- ne contient **aucun secret** : `.env` et `*.pem` sont exclus par `.dockerignore`, et `publish.sh` le vérifie ;
- reçoit ses secrets **à l'exécution** : `--env-file .env` et clés montées en lecture seule (`:ro`) ;
- a une sonde `HEALTHCHECK` sur `/api/v1/health`, et l'étiquette de version `1.0.0` (pas seulement `latest`).

Sur macOS, si le port 5000 est pris (AirPlay) : `REGISTRY_PORT=5001 scripts/publish.sh`.

## 8. Secrets

Aucun mot de passe, aucune clé, aucun `.env` dans le dépôt **ni dans son historique**. Vérification :

```bash
scripts/check-secrets.sh      # fichiers suivis, historique complet, puis analyse gitleaks
```

Si un secret a été commité un jour, le supprimer dans un nouveau commit ne suffit pas : il reste dans l'historique.
Il faut **changer le secret** (nouveau mot de passe, nouvelles clés), puis réécrire l'historique (`git filter-repo`).
Les mots de passe présents dans `tests/conftest.py` sont **fictifs** et ne servent qu'aux tests (clés générées à la volée).

## 9. Limites connues

- **Stockage en mémoire** : toutes les tâches sont perdues à chaque redémarrage du conteneur, et le compteur
  d'identifiants repart à 1.
- **Un seul processus** gunicorn (`--workers 1`, plusieurs threads) : plusieurs processus auraient chacun leur propre
  mémoire, donc leur propre liste. Conséquence : **une seule instance** possible, pas de mise à l'échelle horizontale.
- **Mémoire non bornée** : aucune limite au nombre de tâches ; un client authentifié peut faire grossir la mémoire
  jusqu'à l'arrêt du conteneur (le corps d'une requête est limité à 1 Mo, mais pas le nombre de requêtes).
- **Pas de pagination** sur `GET /todos`.
- **Deux comptes fixes** dont les mots de passe viennent de l'environnement ; pas de gestion d'utilisateurs, pas de
  révocation de jeton avant son expiration, pas de limitation des tentatives de login.
- **PATCH concurrents** : la dernière modification l'emporte (pas de contrôle de version de type `ETag`/`If-Match`).
- **Swagger UI** est chargée depuis un CDN : sans accès Internet, la page `/api/docs` reste blanche
  (le contrat brut `/api/openapi.yaml` reste disponible).

## 10. Passer en production

Nous ne l'avons pas fait, mais voici ce qu'il faudrait pour exposer cette API sur Internet :

1. **HTTPS obligatoire** : un *reverse proxy* (Nginx, Traefik, Caddy) devant l'API termine le TLS avec un certificat
   (Let's Encrypt) et redirige HTTP vers HTTPS. Sans HTTPS, un jeton Bearer intercepté donne l'accès complet.
   L'API elle-même reste sur un réseau privé, jamais exposée directement.
2. **Secrets dans un coffre** : remplacer `.env` et le volume de clés par un gestionnaire de secrets (Vault,
   Docker/Kubernetes secrets, AWS Secrets Manager), avec une **rotation** des clés JWT et des mots de passe.
3. **Une vraie base de données** (PostgreSQL) : les tâches survivent aux redémarrages, et l'on peut lancer plusieurs
   instances derrière un répartiteur de charge.
4. **De vrais comptes** : utilisateurs en base avec mots de passe hachés (argon2, bcrypt), ou mieux, un fournisseur
   d'identité (OIDC : Keycloak, Entra ID) qui émet les jetons.
5. **Protection contre les abus** : limitation du débit (*rate limiting*, en particulier sur `/auth/login` : 429 avec
   `Retry-After`), pagination de `GET /todos`, taille maximale par client.
6. **CORS** restreint aux seules origines autorisées si un front web appelle l'API.
7. **Supervision** : journaux centralisés (sans jetons ni mots de passe), métriques, alertes ; la route `/health` sert
   déjà aux sondes de l'orchestrateur.
8. **Chaîne CI/CD** : à chaque push, tests, construction de l'image, analyse de vulnérabilités (Trivy, Docker Scout),
   Newman contre l'image, puis publication sur un registre privé **authentifié et en HTTPS** (pas un `registry:2` local).
9. **Durcissement du conteneur** : système de fichiers en lecture seule (`--read-only`), `--cap-drop ALL`, limites de
   mémoire et de CPU, image de base mise à jour régulièrement.
10. **Sauvegardes** de la base et un plan de reprise testé.

## 11. Structure

```
app/
  routes/todos.py     routes todos : protection, validation, codes de retour
  schemas.py          validation des entrées (création, PATCH)
  store.py            stockage en mémoire thread-safe (create, find, update, delete)
  security.py         JWT RS256 et décorateur @require_auth (fourni)
  errors.py           gestion centralisée des erreurs (fournie)
docs/                 contrat OpenAPI et schémas
postman/              collection, environnements, rapports Newman
scripts/              publication, lancement de l'image, Newman, contrôle des secrets
tests/                tests pytest
Dockerfile            cibles dev et prod
```
