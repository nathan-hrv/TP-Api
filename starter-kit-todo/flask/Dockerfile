# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Base commune : Python et dépendances d'exécution, utilisateur non root
# ---------------------------------------------------------------------------
FROM python:3.12-slim AS base
ARG UID=1000
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1
RUN useradd --create-home --uid "${UID}" app
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt

# ---------------------------------------------------------------------------
# Développement : code monté depuis l'hôte, rechargement automatique, outils de test
#
# UN SEUL processus (--workers 1) et plusieurs threads (--threads 4) : les tâches sont gardées en mémoire,
# et chaque processus aurait SA PROPRE mémoire. Les threads, eux, partagent la mémoire du processus.
# ---------------------------------------------------------------------------
FROM base AS dev
COPY requirements-dev.txt .
RUN pip install -r requirements-dev.txt
USER app
EXPOSE 8080
CMD ["gunicorn", "--reload", "--workers", "1", "--threads", "4", "--bind", "0.0.0.0:8080", "--access-logfile", "-", "app:create_app()"]

# ---------------------------------------------------------------------------
# Production : seul le code nécessaire est copié dans l'image
#
#   docker build --target prod -t todo-api:1.0.0 .
#
# - aucun secret dans l'image : .env et *.pem sont exclus par .dockerignore ; mots de passe et clés sont fournis
#   À L'EXÉCUTION (docker run --env-file .env, clés montées en lecture seule) ;
# - ni tests ni outils de dev (pytest...) : seules les dépendances d'exécution de l'étape base ;
# - le code appartient à root et l'API tourne sous « app » : même si l'API était compromise, son processus ne peut pas modifier son code.
# ---------------------------------------------------------------------------
FROM base AS prod
LABEL org.opencontainers.image.title="todo-api" \
      org.opencontainers.image.version="1.0.0" \
      org.opencontainers.image.description="API REST de gestion de tâches (ESGI)"
COPY app ./app
COPY docs ./docs
USER app
EXPOSE 8080
# Sonde de santé : Docker marque le conteneur « unhealthy » si /health ne répond plus (python, car pas de curl dans l'image)
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD ["python", "-c", "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8080/api/v1/health', timeout=2)"]
CMD ["gunicorn", "--workers", "1", "--threads", "8", "--bind", "0.0.0.0:8080", "--access-logfile", "-", "app:create_app()"]
