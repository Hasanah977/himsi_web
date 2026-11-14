<?php

namespace App\Http\Middleware;

use App\Support\KbhActivity;
use App\Support\RecruitmentControl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HimsiAccess
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $role = null,
        ?string $department = null
    ): Response {
        $user = $request->user();

        abort_unless($user, 401);

        if ($request->routeIs('logout')) {
            return $next($request);
        }
        if ($user->awaitingRegistration()) {
            return redirect()->route('registration.review');
        }

        if (! $user->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Akun Anda sedang dinonaktifkan.',
                ]);
        }

        if ($role !== null && ! $user->isSuperAdmin()) {
            abort_unless($user->role === $role, 403);

            if ($department !== null) {
                abort_unless(
                    $user->department === $department,
                    403
                );
            }
        }

        if ($user->isSuperAdmin() && $request->routeIs('kbh.*') && ! $request->routeIs('kbh.forum', 'kbh.forum.*')) {
            $request->session()->forget('administration_preview_role');
            abort_unless($request->isMethod('GET'), 403);

            return redirect()->route($request->routeIs('kbh.notifikasi.*') ? 'administration.notifications' : 'admin.dashboard');
        }

        if ($user->isSuperAdmin() && $request->isMethod('GET')) {
            if ($request->routeIs('admin.*') || ($request->route('department') && $request->route('department') !== 'umum')) {
                $request->session()->forget('administration_preview_role');
            } else {
                $previewRole = ['kbh' => 'kbh', 'ketua-umum' => 'ketua_umum', 'sekretaris' => 'sekretaris_umum', 'pengawas' => 'koordinator_pengawas', 'bendahara' => 'bendahara_umum'][$request->segment(1)] ?? $request->session()->get('administration_preview_role');
                if (in_array($previewRole, ['kbh', 'ketua_umum', 'sekretaris_umum', 'koordinator_pengawas', 'bendahara_umum'], true)) {
                    $request->attributes->set('administration_preview_role', $previewRole);
                    $request->session()->put('administration_preview_role', $previewRole);
                }
            }
        }

        $visitedAt = now();
        if ($request->routeIs('recruitment.*', 'kbh.voting.*', 'kbh.seleksi.*', 'kpum.registration.*', 'kpum.vote', 'kpum.vote.submit', 'administration.selection*')) {
            abort_unless(RecruitmentControl::enabled(), 403, 'Rekrutmen, voting, dan seleksi sedang dinonaktifkan oleh Super Admin.');
        }
        $response = $next($request);
        if ($user->role === 'kbh' && $request->isMethod('GET') && $response->isSuccessful()) {
            KbhActivity::recordVisit($request, $visitedAt);
        }

        return $response;
    }
}
