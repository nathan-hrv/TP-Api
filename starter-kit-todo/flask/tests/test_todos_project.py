"""Tests d'API du projet : PATCH, DELETE et filtre ?done (partie 2.4 a).

Le stockage est partagé par tous les tests : chaque test crée ses propres tâches et ne suppose jamais
que la liste est vide.
"""

from tests.conftest import bearer


def create(client, token, title="Tâche de test"):
    response = client.post("/api/v1/todos", json={"title": title}, headers=bearer(token))
    assert response.status_code == 201
    return response.get_json()["data"]


# ---------- PATCH /todos/<id> ----------

def test_patch_nominal_returns_200_and_the_modified_todo(client, user_token):
    todo = create(client, user_token, "Renouveler le certificat")

    response = client.patch(f"/api/v1/todos/{todo['id']}", json={"done": True}, headers=bearer(user_token))

    assert response.status_code == 200
    data = response.get_json()["data"]
    assert data["id"] == todo["id"]
    assert data["done"] is True
    assert data["title"] == "Renouveler le certificat"  # champ non envoyé : inchangé
    assert data["created_at"] == todo["created_at"]


def test_patch_title_and_done_together(client, user_token):
    todo = create(client, user_token)

    response = client.patch(f"/api/v1/todos/{todo['id']}",
                            json={"title": "Renouveler le certificat du proxy", "done": False},
                            headers=bearer(user_token))

    assert response.status_code == 200
    assert response.get_json()["data"]["title"] == "Renouveler le certificat du proxy"


def test_patch_is_idempotent(client, user_token):
    todo = create(client, user_token)
    url = f"/api/v1/todos/{todo['id']}"

    first = client.patch(url, json={"done": True}, headers=bearer(user_token)).get_json()
    second = client.patch(url, json={"done": True}, headers=bearer(user_token)).get_json()

    assert first == second


def test_patch_ignores_id_and_created_at(client, user_token):
    todo = create(client, user_token)

    response = client.patch(f"/api/v1/todos/{todo['id']}",
                            json={"done": True, "id": 999999, "created_at": "2000-01-01T00:00:00Z"},
                            headers=bearer(user_token))

    assert response.status_code == 200
    data = response.get_json()["data"]
    assert data["id"] == todo["id"]
    assert data["created_at"] == todo["created_at"]


def test_patch_unknown_todo_is_404(client, user_token):
    response = client.patch("/api/v1/todos/999999999", json={"done": True}, headers=bearer(user_token))

    assert response.status_code == 404
    assert "message" in response.get_json()


def test_patch_empty_body_is_422(client, user_token):
    todo = create(client, user_token)

    response = client.patch(f"/api/v1/todos/{todo['id']}", json={}, headers=bearer(user_token))

    assert response.status_code == 422
    assert "body" in response.get_json()["errors"]


def test_patch_invalid_title_is_422(client, user_token):
    todo = create(client, user_token)
    url = f"/api/v1/todos/{todo['id']}"

    for invalid in ("", "   ", "x" * 101, 42, None):
        response = client.patch(url, json={"title": invalid}, headers=bearer(user_token))
        assert response.status_code == 422, repr(invalid)
        assert "title" in response.get_json()["errors"]


def test_patch_done_must_be_a_json_boolean(client, user_token):
    todo = create(client, user_token)

    for invalid in ("true", 1, None):
        response = client.patch(f"/api/v1/todos/{todo['id']}", json={"done": invalid}, headers=bearer(user_token))
        assert response.status_code == 422, repr(invalid)
        assert "done" in response.get_json()["errors"]


# ---------- DELETE /todos/<id> ----------

def test_delete_as_admin_is_204_then_the_todo_is_gone(client, admin_token):
    todo = create(client, admin_token)
    url = f"/api/v1/todos/{todo['id']}"

    response = client.delete(url, headers=bearer(admin_token))
    assert response.status_code == 204
    assert response.data == b""  # 204 : aucun corps

    assert client.get(url, headers=bearer(admin_token)).status_code == 404
    assert client.delete(url, headers=bearer(admin_token)).status_code == 404  # 2e suppression : 404


def test_delete_unknown_todo_is_404(client, admin_token):
    response = client.delete("/api/v1/todos/999999999", headers=bearer(admin_token))

    assert response.status_code == 404
    assert "message" in response.get_json()


# ---------- GET /todos?done= ----------

def test_filter_returns_only_matching_todos_in_id_order(client, user_token):
    done_todo = create(client, user_token, "Terminée")
    open_todo = create(client, user_token, "À faire")
    client.patch(f"/api/v1/todos/{done_todo['id']}", json={"done": True}, headers=bearer(user_token))

    done_list = client.get("/api/v1/todos?done=true", headers=bearer(user_token))
    open_list = client.get("/api/v1/todos?done=false", headers=bearer(user_token))

    assert done_list.status_code == open_list.status_code == 200
    done_items, open_items = done_list.get_json()["data"], open_list.get_json()["data"]
    assert all(t["done"] is True for t in done_items)
    assert all(t["done"] is False for t in open_items)
    assert done_todo["id"] in [t["id"] for t in done_items]
    assert open_todo["id"] in [t["id"] for t in open_items]
    assert done_todo["id"] not in [t["id"] for t in open_items]
    for items in (done_items, open_items):
        ids = [t["id"] for t in items]
        assert ids == sorted(ids)  # le filtre ne change pas l'ordre


def test_filter_with_an_invalid_value_is_422(client, user_token):
    for invalid in ("maybe", "1", "TRUE", ""):
        response = client.get(f"/api/v1/todos?done={invalid}", headers=bearer(user_token))
        assert response.status_code == 422, repr(invalid)
        assert "done" in response.get_json()["errors"]
