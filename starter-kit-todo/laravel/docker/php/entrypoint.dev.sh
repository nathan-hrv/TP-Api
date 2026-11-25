#!/bin/sh
# Point d'entrée du conteneur de développement.
set -e
cd /var/www/html

# Les tests (service "tests") n'ont pas besoin de .env ; l'API, si
if [ "$1" = "php" ] && [ "$2" = "artisan" ] && [ "$3" = "serve" ] && [ ! -f .env ]; then
  echo "ERREUR : fichier .env absent. Exécutez 'cp .env.example .env' sur l'hôte puis relancez." >&2
  exit 1
fi

# Premier démarrage : installation des dépendances dans le dossier monté (vendor/ visible sur l'hôte)
if [ ! -f vendor/autoload.php ]; then
  echo "Installation des dépendances Composer (premier démarrage, quelques minutes)..."
  composer install --no-interaction --prefer-dist
fi

# Clé d'application générée si elle est vide
if [ -f .env ] && grep -q '^APP_KEY=$' .env; then
  php artisan key:generate --force
fi

exec "$@"
