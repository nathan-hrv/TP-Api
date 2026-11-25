<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Erreur volontaire du code (404, 409...), rendue en JSON par bootstrap/app.php.
 * Usage : throw new ApiException(404, 'Todo not found.');
 */
class ApiException extends HttpException
{
    public function __construct(int $status, string $message, array $headers = [])
    {
        if ($status === 401) {
            $headers['WWW-Authenticate'] = 'Bearer';   // exigé par la RFC 6750
        }

        parent::__construct($status, $message, null, $headers);
    }
}
