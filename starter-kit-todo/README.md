# Starter kits Todo : API de gestion de tâches

Squelettes du TP 3 (chapitre 3, version 2) du cours *API REST pour l'administration système* (ESGI, 4e année, Systèmes, Réseaux et Cloud Computing).
Trois piles au choix, **un seul contrat** : la même collection Postman valide les trois. Aucune base de données : les tâches sont gardées en mémoire.

| Dossier | Pile | TP | Authentification |
| --- | --- | --- | --- |
| [`quarkus/`](quarkus/) | Java 21, Quarkus 3 | `cours/tp3-v2-java.md` | JWT RS256 (SmallRye JWT) |
| [`flask/`](flask/) | Python 3.12, Flask 3, gunicorn | `cours/tp3-v2-python.md` | JWT RS256 (PyJWT) |
| [`laravel/`](laravel/) | PHP 8.4, Laravel 12 | `cours/tp3-v2-php.md` | JWT RS256 (firebase/php-jwt) |

Chaque dossier a son propre README (démarrage, fichiers fournis, fichiers à écrire, commandes).

## Points communs aux trois kits

| Élément                  | Valeur                                                                                     |
| ------------------------ | ------------------------------------------------------------------------------------------ |
| URL de l'API             | `http://localhost:8080/api/v1` (publiée sur `127.0.0.1` uniquement)                        |
| Route publique d'exemple | `GET /health`                                                                              |
| Authentification         | `POST /auth/login` (`{"username", "password"}`) renvoie un JWT ; comptes `admin` et `user` |
| Route protégée d'exemple | `GET /me`                                                                                  |
| Routes à écrire au TP 3  | `POST /todos` (201 + `Location`), `GET /todos`, `GET /todos/{id}` (404)                    |
| Identifiant              | Entier incrémental attribué par le serveur                                                 |
| Format des réponses      | `{"data": ...}` ; erreurs `{"message": "..."}`, plus `errors` par champ pour les 422       |
| Erreurs                  | Gestion centralisée fournie par le squelette                                               |
| Tests                    | Tests du socle verts, tests du TP 3 rouges au départ                                       |
| Collection Postman       | `postman/` (identique dans les trois kits)                                                 |

## Prérequis

Docker Desktop (ou Docker Engine et Docker Compose v2) et Postman. Aucun Java, Python ni PHP sur le poste.

> Ces kits n'ont pas encore été exécutés de bout en bout : au premier lancement, signalez tout écart avec la sortie de `docker compose logs`.
