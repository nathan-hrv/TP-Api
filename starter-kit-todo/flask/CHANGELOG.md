# Changelog

Toutes les modifications notables de ce projet sont consignées ici.
Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions selon le
[versionnage sémantique](https://semver.org/lang/fr/).

## [1.0.0] - 2026-11-24

Première version livrable, à partir de l'API du TP 3.

### Ajouté
- `PATCH /api/v1/todos/{id}` : modification partielle du titre et/ou de l'état (200, 404, 422).
- `DELETE /api/v1/todos/{id}` : suppression (204 sans corps, 404), réservée au rôle admin.
- Filtre `GET /api/v1/todos?done=true|false` (422 pour toute autre valeur), ordre par identifiant conservé.
- `TodoStore.update()` et `TodoStore.delete()`, protégés par le verrou du stockage.
- Schéma `TodoPatchSchema` avec booléen strict (`done` doit être `true` ou `false` en JSON).
- Tests : stockage concurrent (100 créations simultanées), PATCH, DELETE, filtre, sécurité (401, 403),
  exactitude de la documentation.
- Collection Postman complète (authentification, scénario rejouable, cas d'erreur, validations JSON Schema)
  et environnements `todo-local` (8081) et `todo-image` (8080), sans secret.
- Scripts `publish.sh`, `run-prod.sh`, `newman.sh`, `check-secrets.sh`.
- Schémas de sécurisation et de publication (`docs/schemas.md`).
- `HEALTHCHECK` et étiquettes OCI dans l'image de production.

### Modifié
- Toutes les routes todos exigent un jeton JWT valide (rôle user ou admin).
- Tests du TP 3 : envoi d'un jeton valide, sans autre changement.
- Contrat OpenAPI : toutes les routes, toutes leurs réponses, sécurité Bearer, paramètre `done`.
- Image de production : code détenu par root, en lecture seule pour l'utilisateur `app`.
- Erreurs 422 du corps et du filtre au format de validation uniforme (`message` + `errors`).

### Sécurité
- Aucun secret dans le dépôt ni dans l'image : mots de passe dans `.env`, clés RSA dans un volume Docker,
  fournis à l'exécution.

## [0.3.0] - TP 3

### Ajouté
- `POST /api/v1/todos`, `GET /api/v1/todos`, `GET /api/v1/todos/{id}`, stockage en mémoire.
