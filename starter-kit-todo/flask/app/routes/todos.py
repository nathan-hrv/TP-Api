"""Routes /api/v1/todos.

Sécurité (projet, partie 2.2) :
  - toute route todos exige un jeton valide : @require_auth()          -> 401 sinon
  - DELETE est réservé au rôle admin   : @require_auth(ROLE_ADMIN)     -> 403 pour un simple user
Validation : aucune donnée du client n'est utilisée sans contrôle (schémas marshmallow, ou contrôle explicite
du paramètre ?done). Toute erreur est LEVÉE, et transformée en JSON uniforme par app/errors.py.
"""

from flask import Blueprint, jsonify, request, url_for
from marshmallow import ValidationError

from ..errors import ApiError
from ..schemas import TodoInputSchema, TodoPatchSchema
from ..security import ROLE_ADMIN, require_auth
from ..store import store

bp = Blueprint("todos", __name__, url_prefix="/api/v1")

_DONE_VALUES = {"true": True, "false": False}


def _json_object() -> dict:
    """Le corps de la requête, qui doit être un objet JSON ; sinon 422 au format de validation."""
    payload = request.get_json(silent=True)
    if not isinstance(payload, dict):
        raise ValidationError({"body": ["A JSON object is expected."]})
    return payload


# POST /api/v1/todos
@bp.post("/todos")
@require_auth()  # user ou admin
def create_todo():
    data = TodoInputSchema().load(_json_object())  # title obligatoire, non vide, 100 caractères au plus
    todo = store.create(data["title"])
    location = url_for("todos.get_todo", todo_id=todo.id)  # en-tête Location : URL de la ressource créée
    return jsonify(data=todo.to_dict()), 201, {"Location": location}


# GET /api/v1/todos et GET /api/v1/todos?done=true|false
@bp.get("/todos")
@require_auth()
def list_todos():
    todos = store.find_all()  # déjà triées par identifiant

    done_param = request.args.get("done")
    if done_param is not None:
        if done_param not in _DONE_VALUES:  # ?done=maybe, ?done=1, ?done=TRUE... -> 422
            raise ValidationError({"done": ["Must be 'true' or 'false'."]})
        wanted = _DONE_VALUES[done_param]
        todos = [t for t in todos if t.done is wanted]  # filtrer ne change pas l'ordre

    return jsonify(data=[todo.to_dict() for todo in todos]), 200


# GET /api/v1/todos/{id}
@bp.get("/todos/<int:todo_id>")
@require_auth()
def get_todo(todo_id: int):
    todo = store.find_by_id(todo_id)
    if todo is None:
        raise ApiError(404, "Todo not found.")
    return jsonify(data=todo.to_dict()), 200


# PATCH /api/v1/todos/{id}
@bp.patch("/todos/<int:todo_id>")
@require_auth()
def update_todo(todo_id: int):
    # Validation AVANT la recherche : un corps invalide donne 422, même sur un id inconnu
    data = TodoPatchSchema().load(_json_object())  # {} -> 422 ; title invalide -> 422 ; done non booléen -> 422
    updated = store.update(todo_id, title=data.get("title"), done=data.get("done"))
    if updated is None:
        raise ApiError(404, "Todo not found.")
    return jsonify(data=updated.to_dict()), 200


# DELETE /api/v1/todos/{id}
@bp.delete("/todos/<int:todo_id>")
@require_auth(ROLE_ADMIN)  # réservé aux administrateurs
def delete_todo(todo_id: int):
    if not store.delete(todo_id):
        raise ApiError(404, "Todo not found.")  # 2e suppression : 404 (l'état final, lui, est identique)
    return "", 204  # 204 No Content : aucun corps
