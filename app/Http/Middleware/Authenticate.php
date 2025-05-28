<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    protected function unauthenticated($request, array $guards): void
    {
        // Se for API (aceita JSON), lança 401 sem redirecionar
        if ($request->expectsJson()) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated');
        }

        // Caso contrário, usa o fluxo normal (web)
        parent::unauthenticated($request, $guards);
    }
}
