<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKetuaUntukEskalasi
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->administration_role === 'Ketua Himpunan', 403);

        return $next($request);
    }
}
