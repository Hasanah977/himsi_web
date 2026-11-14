<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Http\Controllers\kbh\IuranController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberDuesController extends Controller
{
    private function authorizeMember(Request $request): void
    {
        abort_unless($request->user()->role === 'anggota_departemen', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeMember($request);

        return app(IuranController::class)->index($request);
    }

    public function pay(Request $request, int $bulan): View
    {
        $this->authorizeMember($request);

        return app(IuranController::class)->bayar($request, $bulan);
    }

    public function store(Request $request, int $bulan): RedirectResponse
    {
        $this->authorizeMember($request);

        return app(IuranController::class)->store($request, $bulan);
    }
}
