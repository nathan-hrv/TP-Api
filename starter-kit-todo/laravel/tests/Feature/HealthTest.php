<?php

/* Test du socle fourni : la route publique répond sans jeton. Vert dès le départ. */

it('répond sur le point de santé sans jeton', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});
