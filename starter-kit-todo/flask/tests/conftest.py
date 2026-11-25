"""Fixtures partagées : application de test, client HTTP, jeton. Les tests génèrent leurs propres clés RSA :
ils ne dépendent ni du volume de clés ni de Docker Compose."""

import pytest
from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.asymmetric import rsa

from app import create_app
from app.config import Config

ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "Admin-Test-Password-1"  # mots de passe FICTIFS, propres aux tests (jamais ceux du .env)
USER_USERNAME = "user"
USER_PASSWORD = "User-Test-Password-1"


def _generate_keypair() -> tuple[str, str]:
    key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
    private_pem = key.private_bytes(
        serialization.Encoding.PEM, serialization.PrivateFormat.PKCS8, serialization.NoEncryption()
    ).decode()
    public_pem = key.public_key().public_bytes(
        serialization.Encoding.PEM, serialization.PublicFormat.SubjectPublicKeyInfo
    ).decode()
    return private_pem, public_pem


_PRIVATE, _PUBLIC = _generate_keypair()


class TestConfig(Config):
    TESTING = True
    JWT_PRIVATE_KEY = _PRIVATE
    JWT_PUBLIC_KEY = _PUBLIC
    ADMIN_PASSWORD = ADMIN_PASSWORD
    USER_PASSWORD = USER_PASSWORD


@pytest.fixture
def app():
    return create_app(TestConfig)


@pytest.fixture
def client(app):
    return app.test_client()


@pytest.fixture
def admin_token(client) -> str:
    response = client.post("/api/v1/auth/login", json={"username": ADMIN_USERNAME, "password": ADMIN_PASSWORD})
    assert response.status_code == 200, response.get_json()
    return response.get_json()["token"]


@pytest.fixture
def user_token(client) -> str:
    """Jeton du compte « user » (rôle user) : peut tout faire sauf supprimer."""
    response = client.post("/api/v1/auth/login", json={"username": USER_USERNAME, "password": USER_PASSWORD})
    assert response.status_code == 200, response.get_json()
    return response.get_json()["token"]


def bearer(token: str) -> dict:
    """En-tête d'authentification à passer en headers=... d'une requête de test."""
    return {"Authorization": f"Bearer {token}"}
