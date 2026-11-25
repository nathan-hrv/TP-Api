<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;

return [

    'default' => env('LOG_CHANNEL', 'stderr'),

    'channels' => [
        // Journaux sur la sortie d'erreur : collectés par Docker (docker compose logs app)
        'stderr' => [
            'driver'  => 'monolog',
            'level'   => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'with'    => ['stream' => 'php://stderr'],
        ],
        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],
    ],

];
