<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentAccess
{
    public function handle(Request $request, Closure $next, string $mode = 'coordinator'): Response
    {
        $user = $request->user();
        $department = $request->route('department');
        if ($mode === 'forum' && $department === 'umum') {
            abort_unless($user->is_active && isset(config('superadmin.roles')[$user->role]) && $user->role !== 'pending', 403);

            return $next($request);
        }
        abort_unless(array_key_exists($department, config('superadmin.departments')), 404);
        $roles = in_array($mode, ['member', 'forum'], true) ? ['koordinator_departemen', 'anggota_departemen'] : ['koordinator_departemen'];
        abort_unless($user->isSuperAdmin() || (in_array($user->role, $roles, true) && $user->department === $department), 403);

        return $next($request);
    }
}
