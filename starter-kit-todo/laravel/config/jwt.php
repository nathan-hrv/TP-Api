<?php

// JWT signés en RS256 : clé privée pour signer, clé publique pour vérifier.
return [

    'issuer' => 'https://todo.example.test',

    // Durée de vie des jetons, en secondes : courte, un JWT ne se révoque pas facilement
    'ttl_seconds' => (int) env('TOKEN_TTL_SECONDS', 3600),

    'private_key_path' => env('JWT_PRIVATE_KEY_PATH', '/keys/privateKey.pem'),
    'public_key_path'  => env('JWT_PUBLIC_KEY_PATH', '/keys/publicKey.pem'),

    // Contenu PEM des clés : prioritaire sur le chemin (utilisé par les tests, qui génèrent leurs clés)
    'private_key' => null,
    'public_key'  => null,

];
