#!/usr/bin/env bash
# Lance l'IMAGE DE PRODUCTION hors du docker-compose de développement, secrets fournis à l'exécution :
#   - mots de passe : --env-file .env (le fichier reste sur le poste, il n'est ni dans Git ni dans l'image)
#   - clés JWT      : volume monté en lecture seule (:ro)
# Usage : scripts/run-prod.sh [version]          (défaut : 1.0.0) -> API sur http://localhost:8080
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION="${1:-1.0.0}"
IMAGE="${IMAGE:-todo-api:${VERSION}}"
KEYS_VOLUME="${KEYS_VOLUME:-todo-flask_jwt-keys}"   # nom = <projet compose>_jwt-keys (vérifier : docker volume ls)
NAME=todo-api-prod

[ -f .env ] || { echo "Fichier .env absent : cp .env.example .env, puis renseigner les mots de passe." >&2; exit 1; }

# Les clés sont générées une seule fois par le service « keys » du docker-compose
if ! docker volume inspect "$KEYS_VOLUME" >/dev/null 2>&1; then
  echo "Volume $KEYS_VOLUME absent : génération des clés JWT"
  docker compose run --rm keys
fi

docker rm -f "$NAME" >/dev/null 2>&1 || true
docker run -d --name "$NAME" \
  -p 127.0.0.1:8080:8080 \
  --env-file .env \
  -e JWT_PUBLIC_KEY_PATH=/keys/publicKey.pem \
  -e JWT_PRIVATE_KEY_PATH=/keys/privateKey.pem \
  -v "${KEYS_VOLUME}:/keys:ro" \
  --cap-drop ALL --security-opt no-new-privileges \
  "$IMAGE" >/dev/null

echo -n "Démarrage de $IMAGE"
for _ in $(seq 1 30); do
  if curl -fs http://localhost:8080/api/v1/health >/dev/null 2>&1; then
    echo " : OK"
    curl -s http://localhost:8080/api/v1/health; echo
    echo "Utilisateur du processus : $(docker exec "$NAME" id -un) (uid $(docker exec "$NAME" id -u))"
    exit 0
  fi
  echo -n "."; sleep 1
done
echo; echo "L'API ne répond pas. Journaux :" >&2
docker logs "$NAME" >&2
exit 1
