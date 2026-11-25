"""Stockage des tâches EN MÉMOIRE : aucune base de données.

Conséquences à comprendre (voir le cours, section 3.3) :
 - tout est perdu quand l'application s'arrête ;
 - une seule instance de l'API peut tourner (deux instances auraient chacune leurs données). C'est pour cela que
   gunicorn est lancé avec UN SEUL processus : plusieurs processus auraient chacun leur mémoire ;
 - plusieurs requêtes arrivent EN MÊME TEMPS (gunicorn utilise plusieurs threads) : les modifications de
   l'état partagé doivent être protégées.

Projet : update() (PATCH) et delete() (DELETE) ajoutés, sous le même verrou que create().
"""

import threading
from dataclasses import replace
from datetime import datetime, timezone

from .models import Todo


class TodoStore:
    def __init__(self) -> None:
        self._todos: dict[int, Todo] = {}
        self._next_id = 1
        self._lock = threading.Lock()

    def create(self, title: str) -> Todo:
        """Crée une tâche (id attribué par le serveur, done à False, created_at à l'instant présent, en UTC)."""
        with self._lock:
            todo = Todo(
                self._next_id,
                title,
                False,
                datetime.now(timezone.utc).replace(microsecond=0),
            )
            self._todos[todo.id] = todo
            self._next_id += 1
            return todo

    def find_all(self) -> list[Todo]:
        """Toutes les tâches, dans l'ordre de leur identifiant."""
        with self._lock:
            return sorted(self._todos.values(), key=lambda todo: todo.id)

    def find_by_id(self, todo_id: int) -> Todo | None:
        """Une tâche, ou None si l'identifiant est inconnu."""
        with self._lock:
            return self._todos.get(todo_id)

    def update(self, todo_id: int, title: str | None = None, done: bool | None = None) -> Todo | None:
        """Modifie partiellement une tâche (PATCH) : seuls les champs fournis (non None) changent.

        Lecture, modification et écriture se font SOUS LE VERROU : deux PATCH simultanés ne peuvent pas
        s'entrelacer (sinon l'un pourrait écraser la modification de l'autre). id et created_at ne changent jamais.
        Renvoie la tâche modifiée, ou None si l'identifiant est inconnu.
        """
        with self._lock:
            old = self._todos.get(todo_id)
            if old is None:
                return None
            new = replace(
                old,
                title=title if title is not None else old.title,
                done=done if done is not None else old.done,
            )  # Todo est « frozen » : on crée une nouvelle instance au lieu de modifier l'ancienne
            self._todos[todo_id] = new
            return new

    def delete(self, todo_id: int) -> bool:
        """Supprime une tâche. Renvoie True si elle existait, False sinon (la route en fera un 404)."""
        with self._lock:
            return self._todos.pop(todo_id, None) is not None


# Instance unique, partagée par toute l'application (équivalent d'un @ApplicationScoped)
store = TodoStore()
