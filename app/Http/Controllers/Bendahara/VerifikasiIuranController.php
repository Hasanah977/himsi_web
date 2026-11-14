<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\KbhIuranPembayaran;
use App\Models\NotifikasiHimsi;
use App\Support\FinanceLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifikasiIuranController extends Controller
{
    private function otorisasi(Request $request): void
    {
        abort_unless($request->user()?->administration_role === 'Bendahara', 403);
    }

    public function index(Request $request)
    {
        $this->otorisasi($request);

        return app(FinanceController::class)->billing($request);
    }

    public function putuskan(Request $request, KbhIuranPembayaran $pembayaran)
    {
        $this->otorisasi($request);
        $data = $request->validate([
            'keputusan' => ['required', 'in:lunas,ditolak'],
            'catatan' => ['required_if:keputusan,ditolak', 'nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($pembayaran, $request, $data): void {
            FinanceLedger::lock();
            $terkunci = KbhIuranPembayaran::whereKey($pembayaran->id)->lockForUpdate()->firstOrFail();
            if ($terkunci->status !== 'menunggu_verifikasi') {
                throw ValidationException::withMessages(['keputusan' => 'Pembayaran sudah diproses. Muat ulang halaman.']);
            }
            $terkunci->update([
                'status' => $data['keputusan'],
                'catatan_bendahara' => $data['catatan'] ?? null,
                'diverifikasi_oleh' => $request->user()->id,
                'diverifikasi_pada' => now(),
            ]);
            FinanceLedger::dues($terkunci);
            FinanceLedger::audit('verifikasi-iuran', 'Iuran #'.$terkunci->id.' '.$data['keputusan']);
            NotifikasiHimsi::kirim(
                (int) $pembayaran->user_id,
                $data['keputusan'] === 'lunas' ? 'Iuran Diverifikasi Lunas' : 'Bukti Iuran Ditolak',
                $data['keputusan'] === 'lunas'
                    ? 'Bukti iuran bulan '.$pembayaran->bulan.'/'.$pembayaran->tahun.' telah diterima Bendahara.'
                    : 'Bukti iuran ditolak. Buka menu Iuran untuk melihat catatan dan mengunggah ulang.',
                FinanceLedger::duesUrl($pembayaran->anggota, (int) $pembayaran->tahun), 'iuran'
            );
        }, 3);

        return redirect()->route('finance.billing', ['year' => $pembayaran->tahun, 'month' => $pembayaran->bulan, 'status' => 'menunggu_verifikasi'])
            ->with('success', 'Keputusan verifikasi berhasil disimpan.');
    }
}
