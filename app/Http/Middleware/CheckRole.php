<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!in_array($request->user()->role->value, $roles)) {
            abort(403, 'Brak uprawnień do wykonania tej akcji.');
        }

        return $next($request);
    }
}
