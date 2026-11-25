#!/usr/bin/env bash
# Exécute la collection Postman avec Newman, DEUX FOIS de suite (preuve que le scénario est rejouable).
# Les mots de passe sont lus dans .env et passés en --env-var : ils n'apparaissent ni à l'écran ni dans les fichiers.
#
# Usage : scripts/newman.sh local    -> docker compose (port 8081)
#         scripts/newman.sh image    -> image de production lancée par scripts/run-prod.sh (port 8080)
# Rapports JUnit : postman/reports/newman-<cible>-run<N>.xml (à committer comme preuve d'exécution)
set -euo pipefail
cd "$(dirname "$0")/.."

TARGET="${1:-local}"
case "$TARGET" in
  local) ENV_FILE=postman/todo-local.postman_environment.json; PORT=8081 ;;
  image) ENV_FILE=postman/todo-image.postman_environment.json; PORT=8080 ;;
  *) echo "Usage : $0 local|image" >&2; exit 2 ;;
esac
RUNS="${RUNS:-2}"

[ -f .env ] || { echo "Fichier .env absent." >&2; exit 1; }
ADMIN_PASSWORD="$(grep -E '^APP_ADMIN_PASSWORD=' .env | cut -d= -f2-)"
USER_PASSWORD="$(grep -E '^APP_USER_PASSWORD=' .env | cut -d= -f2-)"
mkdir -p postman/reports

run_newman() {
  if command -v newman >/dev/null 2>&1; then
    newman "$@"
  elif command -v npx >/dev/null 2>&1; then
    npx --yes newman@6 "$@"
  elif [ "$(uname -s)" = "Linux" ]; then
    # Sans Node, sous Linux : image officielle de Newman sur le RÉSEAU DE L'HÔTE (--network host),
    # car l'API n'écoute que sur 127.0.0.1, injoignable depuis le réseau Docker par défaut
    docker run --rm --network host -v "$PWD:/etc/newman" -w /etc/newman postman/newman:6-alpine "$@"
  else
    # Sans Node, sous macOS / Windows (Docker Desktop) : l'hôte est joignable par host.docker.internal
    docker run --rm -v "$PWD:/etc/newman" -w /etc/newman postman/newman:6-alpine "$@" \
      --env-var "base_url=http://host.docker.internal:${PORT}/api/v1"
  fi
}

for run in $(seq 1 "$RUNS"); do
  echo "===== Newman ($TARGET) : exécution $run / $RUNS"
  run_newman run postman/todo-api.postman_collection.json \
    -e "$ENV_FILE" \
    --env-var "admin_password=${ADMIN_PASSWORD}" \
    --env-var "user_password=${USER_PASSWORD}" \
    --reporters cli,junit \
    --reporter-junit-export "postman/reports/newman-${TARGET}-run${run}.xml"
done
echo "OK : $RUNS exécutions sans échec. Rapports dans postman/reports/"
