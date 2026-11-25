# Compte rendu — TP 3 : API REST Todo (Flask)

## Questions

### 1. Différence entre `health.py` et `me.py`

`GET /api/v1/health` est une route publique : elle vérifie que le service répond et renvoie `200` sans jeton. `GET /api/v1/me` est protégée par `@require_auth()` : sans jeton Bearer valide, elle renvoie `401`; avec un jeton valide, elle renvoie l'identité et les rôles contenus dans le jeton.

### 2. Contenu et lecture d'un JWT

La charge utile du JWT contient :

- `iss` : l'émetteur du jeton (`https://todo.example.test`) ;
- `upn` : le nom de l'utilisateur ;
- `groups` : ses rôles, par exemple `["admin"]` ;
- `exp` : sa date d'expiration au format timestamp Unix.

La charge utile est encodée en Base64 URL-safe, pas chiffrée : elle est donc lisible sans clé. La signature RS256, vérifiée avec la clé publique, permet au serveur de détecter une modification du jeton. Il ne faut jamais partager un jeton, même si son contenu est lisible.

### 3. Adresses auxquelles l'API est joignable

La configuration Docker publie `127.0.0.1:8081` sur le poste et le relie au port `8080` du conteneur (`127.0.0.1:8081:8080`). L'API est donc accessible localement sur `http://localhost:8081`, notamment via `/api/v1/health` et `/api/docs`. Elle n'est pas exposée aux autres machines du réseau par cette configuration.

### 4. Pourquoi ne pas renvoyer la trace dans une réponse 500 ?

Une trace peut révéler des chemins, des détails d'implémentation ou d'autres informations internes utiles à un attaquant. Le client reçoit donc une erreur générique (`{"message":"Server Error."}`), tandis que la trace complète est conservée dans les journaux pour le diagnostic par les développeurs.

### 5. Effet de `docker compose restart api`

Les tâches sont stockées dans la mémoire du processus : le redémarrage les efface et le compteur d'identifiants repart de son état initial. Vérification faite sur une instance Docker isolée : création `201`, redémarrage, puis lecture de l'identifiant créé `404`. L'instance habituelle sur le port 8081 n'a pas été redémarrée pour ne pas interrompre le service.

### 6. Pourquoi un seul processus, et lien avec REST

Chaque processus Gunicorn possède sa propre mémoire. Avec plusieurs workers, une requête pourrait créer une tâche dans un processus et une requête suivante ne pas la retrouver dans un autre. Le verrou protège les accès concurrents entre les threads d'un même processus, pas entre plusieurs processus.

La contrainte REST « sans état » signifie notamment que le serveur ne doit pas dépendre d'une session conservée pour un client entre ses requêtes. Les tâches sont ici des ressources applicatives gardées en mémoire : ce choix pédagogique les rend éphémères et complique la mise à l'échelle. En production, un stockage partagé et durable permettrait plusieurs processus sans perte ni divergence des tâches.

### 7. Pourquoi 422 pour un titre invalide et 404 pour une tâche inconnue ?

`422 Unprocessable Entity` indique que le JSON a été reçu et compris, mais que ses données ne respectent pas les règles métier : ici, le titre est absent, vide ou dépasse 100 caractères. `404 Not Found` signifie que la ressource demandée n'existe pas. Renvoyer `200` avec un contenu vide ferait croire que la lecture a réussi et masquerait l'absence de la tâche.

### 8. Sûreté et idempotence de GET et POST

- `GET /todos` est **sûre** : elle ne modifie pas les tâches. Elle est aussi **idempotente** : la répéter ne change pas l'état du serveur, même si la réponse peut différer si d'autres requêtes créent des tâches entre-temps.
- `POST /todos` n'est pas sûre et n'est pas idempotente : chaque répétition peut créer une nouvelle tâche avec un nouvel identifiant.

Une méthode sûre ne modifie pas l'état demandé au serveur. Une méthode idempotente produit le même effet sur l'état du serveur lorsqu'on l'exécute une ou plusieurs fois.

### 9. Routes à ajouter pour compléter l'API

Il faudrait notamment ajouter :

- `PUT /todos/{id}` pour remplacer les informations modifiables d'une tâche ;
- `PATCH /todos/{id}` pour modifier partiellement une tâche, par exemple la marquer terminée ;
- `DELETE /todos/{id}` pour supprimer une tâche ;
- éventuellement `GET /todos?done=true` pour filtrer les tâches.

`PUT` est idempotente si chaque requête fixe le même état complet. `DELETE` est idempotente du point de vue de l'état final : après la première suppression, la tâche reste absente (les réponses HTTP successives peuvent différer). `PATCH` est idempotente si elle fixe une valeur, par exemple `done=true`, mais pas si elle inverse la valeur à chaque appel. `GET` avec filtre reste sûre et idempotente. `POST` de création n'est généralement pas idempotente.

## Vérifications réalisées

- `docker compose up -d --build` : service démarré sur `127.0.0.1:8081`.
- `GET /api/v1/health` : `200`, statut `ok`.
- `GET /api/docs` : `200`.
- `POST /api/v1/auth/login` : jeton Bearer obtenu ; claims `iss`, `upn`, `groups` et `exp` vérifiés localement sans afficher ni conserver le jeton dans ce compte rendu.
- `GET /api/v1/me` : `401` sans jeton, `200` avec le jeton.
- Création Todo : `201` avec en-tête `Location`; lecture par l'URL retournée : `200`; titre composé uniquement d'espaces : `422`.
- Redémarrage : l'instance de test isolée a perdu la tâche créée ; sa lecture a renvoyé `404`.
- Suite Docker : **19 tests réussis**, y compris les tests Todo et OpenAPI.

La collection Postman et son environnement sont configurés pour `http://localhost:8081/api/v1`. Les vérifications HTTP et les tests automatisés couvrent le contrat ; le lancement du Runner dans l'interface Postman reste une manipulation à faire dans Postman si une preuve d'exécution de cette interface est demandée.
