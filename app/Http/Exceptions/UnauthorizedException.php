<?php

namespace App\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(Response::HTTP_UNAUTHORIZED, $message);
    }
}
