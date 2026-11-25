<?php

/*
 * TP 3 : les trois routes de l'API Todo. Ces tests décrivent le contrat (cours, section 3.2).
 * Ils sont ROUGES au départ. Ne les modifiez pas : faites-les passer au vert.
 *
 *   docker compose run --rm tests php artisan test --filter=TodoApiTest
 *
 * La mémoire APCu est vidée avant chaque test.
 */

function createTodo(string $title): Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/v1/todos', ['title' => $title]);
}

// ---------- POST /todos ----------

it('crée une tâche : 201, en-tête Location et la nouvelle tâche', function () {
    $response = createTodo('Sauvegarder la base')->assertCreated();

    $todo = $response->json('data');
    expect($todo['id'])->toBeInt()->toBeGreaterThan(0)
        ->and($todo['title'])->toBe('Sauvegarder la base')
        ->and($todo['done'])->toBeFalse()
        ->and($todo['created_at'])->not->toBeEmpty();
    expect($response->headers->get('Location'))->toEndWith('/api/v1/todos/'.$todo['id']);
});

it('attribue des identifiants croissants', function () {
    $first = createTodo('Première')->json('data.id');
    $second = createTodo('Seconde')->json('data.id');

    expect($second)->toBeGreaterThan($first);
});

it('exige un titre', function () {
    $this->postJson('/api/v1/todos', [])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors'])
        ->assertJsonValidationErrors(['title']);
});

it('refuse un titre vide ou fait d\'espaces', function (string $blank) {
    createTodo($blank)->assertUnprocessable()->assertJsonValidationErrors(['title']);
})->with(['vide' => [''], 'espaces' => ['   ']]);

it('refuse un titre de plus de 100 caractères', function () {
    createTodo(str_repeat('x', 100))->assertCreated();
    createTodo(str_repeat('x', 101))->assertUnprocessable()->assertJsonValidationErrors(['title']);
});

it('empêche le client de choisir l\'état d\'une nouvelle tâche', function () {
    // Un champ non prévu par le contrat est ignoré : une tâche naît toujours non terminée
    $todo = $this->postJson('/api/v1/todos', ['title' => 'Tentative', 'done' => true, 'id' => 999999])
        ->assertCreated()
        ->json('data');

    expect($todo['done'])->toBeFalse()->and($todo['id'])->not->toBe(999999);
});

// ---------- GET /todos ----------

it('liste les tâches, y compris celle qu\'on vient de créer', function () {
    $id = createTodo('Vérifier les sauvegardes')->json('data.id');

    $todos = $this->getJson('/api/v1/todos')->assertOk()->json('data');

    expect(array_column($todos, 'id'))->toContain($id)
        ->and(array_column($todos, 'title'))->toContain('Vérifier les sauvegardes');
});

it('liste les tâches dans l\'ordre des identifiants', function () {
    createTodo('A');
    createTodo('B');

    $ids = array_column($this->getJson('/api/v1/todos')->assertOk()->json('data'), 'id');
    $sorted = $ids;
    sort($sorted);

    expect($ids)->toBe($sorted);
});

// ---------- GET /todos/{id} ----------

it('affiche une tâche', function () {
    $id = createTodo('Mettre à jour le serveur')->json('data.id');

    $this->getJson("/api/v1/todos/{$id}")
        ->assertOk()
        ->assertJsonPath('data.id', $id)
        ->assertJsonPath('data.title', 'Mettre à jour le serveur')
        ->assertJsonPath('data.done', false);
});

it('renvoie 404 avec un message JSON pour un identifiant inconnu', function () {
    $this->getJson('/api/v1/todos/999999999')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('renvoie 404 pour un identifiant non numérique', function () {
    $this->getJson('/api/v1/todos/abc')->assertNotFound();
});
