<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKoordinatorPengawas
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->administration_role === 'Koordinator Pengawas', 403, 'Hanya Koordinator Pengawas.');

        return $next($request);
    }
}
