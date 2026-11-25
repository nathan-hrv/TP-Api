<?php

// Comptes de démonstration : deux comptes dont les mots de passe viennent de .env.
// Simplification de TP : dans une vraie application, les comptes sont en base avec des mots de passe HACHÉS.
return [

    'users' => [
        'admin' => ['password' => env('APP_ADMIN_PASSWORD'), 'role' => 'admin'],
        'user'  => ['password' => env('APP_USER_PASSWORD'), 'role' => 'user'],
    ],

];
