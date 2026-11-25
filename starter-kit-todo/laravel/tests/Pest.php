<?php

use Tests\TestCase;

/*
 * Tous les tests du dossier Feature héritent de TestCase : application démarrée, clés RSA de test,
 * mémoire APCu vidée avant chaque test.
 */
pest()->extend(TestCase::class)->in('Feature');
