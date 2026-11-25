"""Tests de sécurité (partie 2.2) : sans jeton -> 401 ; rôle insuffisant -> 403 ; format d'erreur uniforme."""

import pytest

from tests.conftest import bearer

PROTECTED = [
    ("get", "/api/v1/todos", None),
    ("post", "/api/v1/todos", {"title": "Sans jeton"}),
    ("get", "/api/v1/todos/1", None),
    ("patch", "/api/v1/todos/1", {"done": True}),
    ("delete", "/api/v1/todos/1", None),
]


@pytest.mark.parametrize("method, url, body", PROTECTED)
def test_todo_routes_without_token_are_401(client, method, url, body):
    response = getattr(client, method)(url, json=body)

    assert response.status_code == 401
    assert response.headers["WWW-Authenticate"] == "Bearer"
    assert response.get_json() == {"message": "Unauthenticated."}


def test_forged_token_is_401(client, user_token):
    header, payload, signature = user_token.split(".")
    forged = f"{header}.{payload}.{signature[::-1]}"  # signature modifiée : le jeton n'est plus authentique

    response = client.get("/api/v1/todos", headers=bearer(forged))

    assert response.status_code == 401


def test_delete_with_user_role_is_403_and_the_todo_still_exists(client, user_token):
    todo = client.post("/api/v1/todos", json={"title": "Protégée"}, headers=bearer(user_token)).get_json()["data"]

    response = client.delete(f"/api/v1/todos/{todo['id']}", headers=bearer(user_token))

    assert response.status_code == 403
    assert response.get_json() == {"message": "This action is unauthorized."}
    assert client.get(f"/api/v1/todos/{todo['id']}", headers=bearer(user_token)).status_code == 200


def test_user_can_read_create_and_patch(client, user_token):
    created = client.post("/api/v1/todos", json={"title": "Par un user"}, headers=bearer(user_token))
    todo_id = created.get_json()["data"]["id"]

    assert created.status_code == 201
    assert client.get("/api/v1/todos", headers=bearer(user_token)).status_code == 200
    assert client.patch(f"/api/v1/todos/{todo_id}", json={"done": True}, headers=bearer(user_token)).status_code == 200


def test_public_routes_need_no_token(client):
    assert client.get("/api/v1/health").status_code == 200
    # login est public : sans jeton, la requête est TRAITÉE (ici refusée pour mauvais identifiants, pas pour jeton absent)
    login = client.post("/api/v1/auth/login", json={"username": "x", "password": "y"})
    assert login.get_json() == {"message": "Invalid credentials."}


def test_errors_never_leak_a_stack_trace(client, user_token):
    response = client.post("/api/v1/todos", data="{pas du json", headers={**bearer(user_token),
                                                                          "Content-Type": "application/json"})

    assert response.status_code == 422
    body = response.get_data(as_text=True)
    assert "Traceback" not in body and "File \"" not in body
