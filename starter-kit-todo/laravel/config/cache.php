<?php

// Le cache n'est pas utilisé par le kit (TodoStore appelle APCu directement) ; il est déclaré pour que
// Laravel démarre. Le pilote "apc" est la mémoire partagée APCu.
return [

    'default' => env('CACHE_STORE', 'apc'),

    'stores' => [
        'apc' => ['driver' => 'apc'],
        'array' => ['driver' => 'array', 'serialize' => false],
    ],

    'prefix' => env('CACHE_PREFIX', 'todo-api-cache-'),

];
