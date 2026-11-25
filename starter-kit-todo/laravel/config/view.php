<?php

// Aucune vue HTML dans une API : valeurs minimales pour que le framework démarre.
return [

    'paths' => [resource_path('views')],
    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views')) ?: storage_path('framework/views')),

];
