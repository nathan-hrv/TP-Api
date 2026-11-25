"""Test UNITAIRE du stockage, sans HTTP : on appelle directement TodoStore.

Ce qu'on vérifie : 100 créations SIMULTANÉES (100 threads) donnent 100 identifiants distincts et aucune tâche perdue.
Sans le verrou de TodoStore, deux threads pourraient lire le même _next_id et se voler un identifiant.
"""

import threading

from app.store import TodoStore

N = 100


def test_100_concurrent_creations_give_100_distinct_ids_and_no_lost_todo():
    store = TodoStore()  # un stockage neuf, isolé des autres tests
    barrier = threading.Barrier(N)  # tous les threads attendent ici, puis démarrent EN MÊME TEMPS
    created_ids = []
    ids_lock = threading.Lock()  # protège la liste du test elle-même

    def worker(i: int) -> None:
        barrier.wait()
        todo = store.create(f"Tâche {i}")
        with ids_lock:
            created_ids.append(todo.id)

    threads = [threading.Thread(target=worker, args=(i,)) for i in range(N)]
    for t in threads:
        t.start()
    for t in threads:
        t.join()

    assert len(created_ids) == N
    assert len(set(created_ids)) == N  # 100 identifiants DISTINCTS
    stored = store.find_all()
    assert len(stored) == N  # aucune tâche perdue
    assert {t.id for t in stored} == set(created_ids)
    assert {t.title for t in stored} == {f"Tâche {i}" for i in range(N)}


def test_update_changes_only_the_given_fields():
    store = TodoStore()
    todo = store.create("Avant")

    updated = store.update(todo.id, done=True)

    assert updated.title == "Avant" and updated.done is True
    assert updated.id == todo.id and updated.created_at == todo.created_at
    assert store.update(999, title="x") is None


def test_delete_returns_false_the_second_time():
    store = TodoStore()
    todo = store.create("À supprimer")

    assert store.delete(todo.id) is True
    assert store.delete(todo.id) is False
    assert store.find_by_id(todo.id) is None
