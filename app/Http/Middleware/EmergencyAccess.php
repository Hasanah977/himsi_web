<?php

namespace App\Http\Middleware;

use App\Support\EmergencyState;
use App\Support\SecurityAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EmergencyAccess
{
    public function __construct(private EmergencyState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        $callback = function () use ($request, $next): Response {
            $state = $this->state->read();
            $user = $request->user();
            if ($user && ! $user->isSuperAdmin() && ($state['frozen'] || (int) $request->session()->get('security_generation', 0) < $state['generation'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => $state['frozen'] ? 'Akses akun sementara dibekukan oleh Super Admin.' : 'Sesi telah diakhiri oleh Super Admin. Silakan login kembali.']);
            }
            if ($state['maintenance'] && $request->routeIs(...config('security.public_routes'))) {
                return response()->view('maintenance-public', [], 503)->header('Retry-After', '300')->header('Cache-Control', 'no-store, private');
            }
            $request->attributes->set('security_audit_active', true);
            try {
                $response = $next($request);
            } finally {
                $request->attributes->remove('security_audit_active');
            }
            if ($response->getStatusCode() === 403) {
                SecurityAudit::record('access_denied', 'Percobaan mengakses halaman atau tindakan tanpa izin.', 'danger', ['method' => $request->method(), 'path' => $request->path()]);
            } elseif (! $request->isMethodSafe() && $response->getStatusCode() < 400 && $user && ! $request->routeIs('admin.security.*', 'login.submit', 'logout')) {
                SecurityAudit::record('activity', 'Permintaan perubahan data diproses.', context: ['method' => $request->method(), 'path' => $request->path()]);
            }

            return $response;
        };

        return $request->routeIs('admin.security.*') ? $callback() : $this->state->traffic($callback);
    }
}
