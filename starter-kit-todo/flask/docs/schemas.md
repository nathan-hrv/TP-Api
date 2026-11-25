# Schémas de la soutenance

GitHub et GitLab affichent ces schémas directement. Pour une diapositive : copier le bloc dans https://mermaid.live
puis exporter en PNG ou SVG.

## 1. Les étapes de sécurisation

Chaque requête passe des contrôles dans cet ordre ; au premier qui échoue, la requête s'arrête et le client reçoit
une erreur JSON uniforme `{ "message": "..." }`, sans aucune trace technique.

```mermaid
sequenceDiagram
    autonumber
    actor C as Client (script, Postman)
    participant L as POST /auth/login
    participant A as require_auth (security.py)
    participant V as Validation (schemas.py)
    participant S as TodoStore (verrou)

    C->>L: username et password
    L->>L: comparaison en temps constant
    alt identifiants faux ou compte inconnu
        L-->>C: 401 Invalid credentials (même message dans les deux cas)
    else identifiants valides
        L-->>C: 200 JWT signé RS256 avec la clé PRIVÉE (iss, upn, groups, exp)
    end

    C->>A: requête todos + en-tête Authorization Bearer jeton
    A->>A: jeton présent ? signature vérifiée avec la clé PUBLIQUE ? émetteur ? non expiré ?
    alt jeton absent, falsifié ou expiré
        A-->>C: 401 Unauthenticated + WWW-Authenticate Bearer
    else DELETE sans le rôle admin
        A-->>C: 403 This action is unauthorized
    else jeton valide et rôle suffisant
        A->>V: corps JSON et paramètre done
        alt donnée invalide
            V-->>C: 422 avec le détail par champ
        else donnée valide
            V->>S: lecture ou écriture sous verrou
            S-->>C: 200, 201 ou 204 (404 si la tâche est inconnue)
        end
    end
```

Autour de ce parcours :

| Couche | Mesure | Où |
| --- | --- | --- |
| Secrets | Mots de passe dans `.env`, clés RSA dans un volume Docker : jamais dans Git, jamais dans l'image | `.gitignore`, `.dockerignore`, `scripts/check-secrets.sh` |
| Authentification | JWT RS256, durée de vie courte, algorithme imposé par le serveur (jamais `none`) | `app/security.py` |
| Autorisation | `@require_auth()` sur toutes les routes todos, `@require_auth(ROLE_ADMIN)` sur DELETE | `app/routes/todos.py` |
| Validation | Schémas marshmallow, champs inconnus ignorés (pas de *mass assignment*), booléen strict, filtre contrôlé | `app/schemas.py` |
| Erreurs | Gestion centralisée : JSON uniforme, 500 sans détail, trace uniquement dans les journaux | `app/errors.py` |
| Exécution | Conteneur sous l'utilisateur `app` (non root), code en lecture seule, API publiée sur `127.0.0.1` | `Dockerfile`, `scripts/run-prod.sh` |

## 2. La chaîne de publication

```mermaid
flowchart LR
    A["Code mergé sur main<br/>pytest vert"] --> B["docker build --target prod<br/>multi-étapes, USER app"]
    B --> C[("Image<br/>todo-api:1.0.0")]
    C --> D["Vérifications<br/>non root, aucun .env ni .pem"]
    D --> E["docker tag<br/>localhost:5000/todo-api:1.0.0"]
    E --> F["docker push"]
    F --> G[("Registre local<br/>registry:2 sur 127.0.0.1:5000")]
    G --> H["Preuve par l'API du registre<br/>curl /v2/_catalog et /tags/list"]
    C --> I["docker run hors compose<br/>--env-file .env, clés en :ro"]
    K[["Secrets sur le poste<br/>.env et volume jwt-keys"]] -. "fournis à l'exécution" .-> I
    I --> J["Newman x2 contre l'image<br/>preuve d'autonomie"]
    J --> T["git tag v1.0.0<br/>git push origin v1.0.0"]
    H --> T
```

En une phrase : **on ne tague que ce qu'on a prouvé**. L'image est construite, vérifiée, publiée, lancée seule
avec ses secrets fournis de l'extérieur, testée par Newman, et seulement ensuite la version est figée par le tag Git.
