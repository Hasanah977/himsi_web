<?php

namespace App\Http\Middleware;

use App\Support\Governance;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GovernanceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        Governance::expire();
        if ($request->user()) {
            $request->user()->refresh();
        }
        if (! $request->isMethodSafe()) {
            foreach ($request->route()?->parameters() ?? [] as $record) {
                if ($record instanceof Model && in_array(class_basename($record), Governance::MODELS, true)) {
                    Governance::assertWritable($record);
                }
            }
        }

        return $next($request);
    }
}
