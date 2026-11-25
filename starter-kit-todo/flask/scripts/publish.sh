#!/usr/bin/env bash
# Chaîne de publication : construire l'image de prod, la vérifier, la pousser sur un registre local, prouver la publication.
# Usage : scripts/publish.sh [version]     (défaut : 1.0.0)
# Sur macOS, le port 5000 est souvent pris par AirPlay : REGISTRY_PORT=5001 scripts/publish.sh
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION="${1:-1.0.0}"
REGISTRY_PORT="${REGISTRY_PORT:-5000}"
REGISTRY="localhost:${REGISTRY_PORT}"
IMAGE=todo-api

echo "===== 1. Construction de l'image de production ${IMAGE}:${VERSION} (cible prod, multi-étapes)"
docker build --target prod -t "${IMAGE}:${VERSION}" .

echo "===== 2. Vérifications de l'image"
echo "Utilisateur par défaut : $(docker run --rm --entrypoint id "${IMAGE}:${VERSION}" -un) (doit être app, pas root)"
echo "Contenu de /app :"; docker run --rm --entrypoint ls "${IMAGE}:${VERSION}" -A /app
if docker run --rm --entrypoint sh "${IMAGE}:${VERSION}" -c 'find /app /home/app -name ".env" -o -name "*.pem" | grep -q .'; then
  echo "ERREUR : un secret (.env ou .pem) est présent dans l'image" >&2; exit 1
fi
echo "Aucun .env ni .pem dans l'image : OK"
docker run --rm --entrypoint sh "${IMAGE}:${VERSION}" -c 'python -c "import pytest"' 2>/dev/null \
  && { echo "ERREUR : les outils de test sont dans l'image" >&2; exit 1; } || echo "Pas d'outils de test dans l'image : OK"

echo "===== 3. Registre local (registry:2 sur 127.0.0.1:${REGISTRY_PORT})"
if [ -z "$(docker ps -q -f name=^registry$)" ]; then
  docker rm -f registry >/dev/null 2>&1 || true
  docker run -d --name registry --restart unless-stopped -p "127.0.0.1:${REGISTRY_PORT}:5000" registry:2 >/dev/null
  sleep 2
fi

echo "===== 4. Étiquetage et envoi (version ${VERSION} ET latest)"
docker tag "${IMAGE}:${VERSION}" "${REGISTRY}/${IMAGE}:${VERSION}"
docker tag "${IMAGE}:${VERSION}" "${REGISTRY}/${IMAGE}:latest"
docker push "${REGISTRY}/${IMAGE}:${VERSION}"
docker push "${REGISTRY}/${IMAGE}:latest"

echo "===== 5. Preuve : le registre est une API REST"
echo "GET /v2/_catalog            -> $(curl -s "http://${REGISTRY}/v2/_catalog")"
echo "GET /v2/${IMAGE}/tags/list  -> $(curl -s "http://${REGISTRY}/v2/${IMAGE}/tags/list")"

cat <<EOF

Étapes suivantes :
  scripts/run-prod.sh ${VERSION}      # lancer l'image hors docker-compose (port 8080)
  scripts/newman.sh image             # rejouer la collection contre l'image
  git tag -a v${VERSION} -m "Version ${VERSION}" && git push origin v${VERSION}
EOF
