<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministrationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->routeIs('kbh.seleksi.index', 'kbh.seleksi.hasil') && in_array($request->user()->role, ['sekretaris_umum', 'bendahara_umum', 'anggota_departemen', 'koordinator_departemen'], true)) {
            return redirect()->route('recruitment.result');
        }
        $role = ['kbh' => 'kbh', 'ketua-umum' => 'ketua_umum', 'sekretaris' => 'sekretaris_umum', 'pengawas' => 'koordinator_pengawas', 'bendahara' => 'bendahara_umum'][$request->segment(1)] ?? null;
        if ($request->user()->isSuperAdmin()) {
            abort_unless($request->isMethodSafe() || $request->routeIs('notifikasi-himsi.baca'), 403, 'Pratinjau Super Admin hanya untuk membaca. Keputusan harus oleh pemilik role.');
            if ($role) {
                $request->attributes->set('administration_preview_role', $role);
            }
        } elseif ($role) {
            abort_unless($request->user()->role === $role, 403);
        }

        return $next($request);
    }
}
