#!/usr/bin/env bash
# Vérifie qu'AUCUN secret n'est dans le dépôt Git, ni dans son HISTORIQUE (un fichier supprimé reste dans l'historique).
# Lancer depuis n'importe où dans le dépôt : scripts/check-secrets.sh
set -uo pipefail
ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"
status=0

echo "===== 1. Fichiers sensibles suivis par Git"
if git ls-files | grep -E '(^|/)\.env$|\.pem$|\.key$|(^|/)keys/' ; then status=1; else echo "aucun : OK"; fi

echo "===== 2. Fichiers sensibles présents un jour dans l'historique"
if git log --all --name-only --pretty=format: | sort -u | grep -E '(^|/)\.env$|\.pem$|\.key$'; then status=1; else echo "aucun : OK"; fi

echo "===== 3. Contenus suspects dans tout l'historique"
if git log --all -p | grep -nE 'BEGIN [A-Z ]*PRIVATE KEY|APP_(ADMIN|USER)_PASSWORD=[^[:space:]]+' | head; then status=1; else echo "aucun : OK"; fi

echo "===== 4. Analyse gitleaks (si Docker est disponible)"
if command -v docker >/dev/null 2>&1; then
  docker run --rm -v "$ROOT:/repo" zricethezav/gitleaks:latest detect --source /repo --no-banner
  rc=$?
  if [ "$rc" -eq 1 ]; then status=1                      # gitleaks : 1 = fuite trouvée
  elif [ "$rc" -ne 0 ]; then echo "gitleaks n'a pas pu s'exécuter (code $rc) : étape ignorée"; fi
else
  echo "Docker absent : étape ignorée"
fi

[ "$status" -eq 0 ] && echo "RÉSULTAT : aucun secret trouvé" || echo "RÉSULTAT : secret(s) trouvé(s) : voir le README, section Secrets"
exit "$status"
