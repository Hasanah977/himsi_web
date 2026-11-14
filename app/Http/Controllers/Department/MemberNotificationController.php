<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Administration\PortalController;
use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return app(PortalController::class)->notifications($request);
    }

    public function readAll(Request $request): RedirectResponse
    {
        NotifikasiHimsi::where('user_id', $request->user()->id)->whereNull('dibaca_pada')->update(['dibaca_pada' => now()]);

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        NotifikasiHimsi::where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Notifikasi Anda telah dihapus.');
    }
}
