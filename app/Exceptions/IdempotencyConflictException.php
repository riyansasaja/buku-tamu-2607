<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class IdempotencyConflictException extends HttpException
{
    public function __construct()
    {
        parent::__construct(409, 'Idempotency-Key telah digunakan untuk data kunjungan yang berbeda.');
    }
}
