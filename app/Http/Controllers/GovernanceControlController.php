<?php

namespace App\Http\Controllers;

use App\Models\PublicContent;
use App\Support\RecruitmentControl;
use App\Support\SecurityAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GovernanceControlController extends Controller
{
    public function index(): View
    {
        return view('admin.governance-control', ['enabled' => RecruitmentControl::enabled()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        DB::transaction(function () use ($data): void {
            PublicContent::updateOrCreate(['section' => 'settings', 'slug' => 'governance'], [
                'payload' => ['recruitment_enabled' => (bool) $data['enabled']],
                'position' => 0, 'is_published' => false,
            ]);
            SecurityAudit::record('recruitment_control', $data['enabled'] ? 'Akses rekrutmen, voting, dan seleksi diaktifkan.' : 'Akses rekrutmen, voting, dan seleksi dinonaktifkan.');
        });

        return back()->with('success', $data['enabled'] ? 'Kontrol kepengurusan diaktifkan.' : 'Kontrol kepengurusan dinonaktifkan.');
    }
}
