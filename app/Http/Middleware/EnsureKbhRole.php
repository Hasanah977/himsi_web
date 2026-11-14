<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKbhRole
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->administration_role === 'KBH', 403);

        return $next($request);
    }
}
