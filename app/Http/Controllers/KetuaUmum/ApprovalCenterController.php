<?php

namespace App\Http\Controllers\KetuaUmum;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Department\ReviewController;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalCenterController extends Controller
{
    public function index(): View
    {
        return app(ReviewController::class)->index(request());
    }

    public function proses(Request $request, Pengajuan $pengajuan)
    {
        abort_unless(
            $request->user()?->administration_role === 'Ketua Himpunan',
            403
        );

        $data = $request->validate([
            'keputusan' => ['required', 'in:disetujui,ditolak'],
            'catatan_ketua' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['keputusan'] === 'ditolak' && blank($data['catatan_ketua'] ?? null)) {
            return back()->withErrors([
                'catatan_ketua' => 'Catatan wajib diisi ketika menolak pengajuan.',
            ]);
        }

        $berhasil = Pengajuan::whereKey($pengajuan->id)
            ->where('status', 'menunggu')
            ->update([
                'status' => $data['keputusan'],
                'catatan_ketua' => $data['catatan_ketua'] ?? null,
                'diproses_oleh' => $request->user()->id,
                'diproses_pada' => now(),
            ]);

        if (! $berhasil) {
            return redirect()->route('ketua-umum.approval-center')
                ->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        return redirect()->route('ketua-umum.approval-center')
            ->with('success', 'Keputusan pengajuan berhasil disimpan.');
    }
}
