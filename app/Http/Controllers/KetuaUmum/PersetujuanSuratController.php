<?php

namespace App\Http\Controllers\KetuaUmum;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use App\Services\NomorSuratService;
use App\Services\NotifikasiSuratService;
use App\Services\PengesahanPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersetujuanSuratController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->administration_role === 'Ketua Himpunan',
            403,
            'Anda tidak memiliki akses ke halaman ini.'
        );

        $jumlahMenunggu = SuratKeluar::query()
            ->where('status', 'menunggu_persetujuan')
            ->count();

        $jumlahDisetujui = SuratKeluar::query()
            ->where('status', 'disetujui')
            ->count();

        $jumlahDitolak = SuratKeluar::query()
            ->where('status', 'ditolak')
            ->count();

        $suratKeluars = SuratKeluar::query()
            ->with('pembuat')
            ->whereIn('status', [
                'menunggu_persetujuan',
                'disetujui',
                'ditolak',
            ])
            ->orderByDesc('diajukan_pada')
            ->orderByDesc('id')
            ->paginate(10);

        return view(
            'ketua-umum.persetujuan-surat.index',
            compact(
                'jumlahMenunggu',
                'jumlahDisetujui',
                'jumlahDitolak',
                'suratKeluars'
            )
        );
    }

    public function show(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        abort_unless(
            $request->user()?->administration_role === 'Ketua Himpunan',
            403,
            'Anda tidak memiliki akses ke halaman ini.'
        );

        abort_unless(
            in_array($suratKeluar->status, [
                'menunggu_persetujuan',
                'disetujui',
                'ditolak',
            ], true),
            404,
            'Surat belum diajukan untuk persetujuan.'
        );

        $suratKeluar->load([
            'pembuat',
            'penyetuju',
        ]);

        $riwayatPersetujuan = DB::table('riwayat_persetujuan_surats')
            ->where('surat_keluar_id', $suratKeluar->id)
            ->orderBy('pengajuan_ke')
            ->get();

        return view(
            'ketua-umum.persetujuan-surat.show',
            compact('suratKeluar', 'riwayatPersetujuan')
        );
    }

    public function putuskan(
        Request $request,
        SuratKeluar $suratKeluar
    ) {
        abort_unless(
            $request->user()?->administration_role === 'Ketua Himpunan',
            403,
            'Anda tidak memiliki akses.'
        );

        $data = $request->validate([
            'keputusan' => 'required|in:disetujui,ditolak',
            'catatan_persetujuan' => 'required|string|max:2000',
            'konfirmasi_tanda_tangan' => 'accepted_if:keputusan,disetujui',
        ]);

        if ($data['keputusan'] === 'disetujui'
            && strtolower(pathinfo($suratKeluar->file_surat ?? '', PATHINFO_EXTENSION)) === 'pdf'
            && ! DB::table('tanda_tangan_surat_penempatans')
                ->where('surat_keluar_id', $suratKeluar->id)
                ->where('user_id', $request->user()->id)->exists()) {
            return back()->withErrors(['ttd' => 'Sebelum menyetujui PDF, unggah dan atur posisi tanda tangan Anda sendiri terlebih dahulu.'])->withInput();
        }

        abort_unless(
            $suratKeluar->status === 'menunggu_persetujuan',
            409,
            'Surat ini sudah diproses.'
        );

        DB::transaction(function () use (
            $suratKeluar,
            $data,
            $request
        ) {
            $surat = SuratKeluar::query()
                ->lockForUpdate()
                ->findOrFail($suratKeluar->getKey());

            abort_unless(
                $surat->status === 'menunggu_persetujuan',
                409,
                'Surat sudah diproses.'
            );

            if (
                $data['keputusan'] === 'disetujui'
                && ! in_array(
                    $surat->kategori_penomoran,
                    ['keputusan', 'reguler', 'kepanitiaan'],
                    true
                )
            ) {
                abort(
                    422,
                    'Kategori penomoran belum dipilih. Minta Sekertaris melengkapi surat.'
                );
            }

            $surat->status = $data['keputusan'];
            $surat->catatan_persetujuan =
                $data['catatan_persetujuan'] ?? null;
            $surat->disetujui_oleh = $request->user()->id;
            $surat->diputuskan_pada = now();
            $surat->save();

            if ($data['keputusan'] === 'disetujui') {
                app(NomorSuratService::class)
                    ->tetapkan($surat);
            }

            $riwayat = DB::table(
                'riwayat_persetujuan_surats'
            );

            $pengajuanKe = (
                (int) $riwayat
                    ->where('surat_keluar_id', $surat->id)
                    ->max('pengajuan_ke')
            ) + 1;

            $riwayat->insert([
                'surat_keluar_id' => $surat->id,
                'pengajuan_ke' => $pengajuanKe,
                'status_keputusan' => $data['keputusan'],
                'catatan' => $surat->catatan_persetujuan,
                'diputuskan_oleh' => $request->user()->id,
                'diputuskan_pada' => $surat->diputuskan_pada,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        $suratKeluar->refresh();
        NotifikasiSuratService::sekretarisKeputusan($suratKeluar,
            $data['keputusan'] === 'disetujui' ? 'disetujui Ketua Umum' : 'ditolak Ketua Umum');
        if ($data['keputusan'] === 'disetujui') {
            if ($suratKeluar->penandatangan_pendamping_id) {
                NotifikasiSuratService::pendampingMenunggu($suratKeluar);
            } else {
                try {
                    app(PengesahanPdfService::class)->terbitkan($suratKeluar);
                } catch (\Throwable $e) {
                    Log::warning('PDF final belum bisa diterbitkan: '.$e->getMessage(), ['surat_id' => $suratKeluar->id]);
                }
            }
        }

        return redirect()
            ->route('ketua-umum.persetujuan-surat.show', $suratKeluar)
            ->with('success', 'Keputusan surat berhasil disimpan.');
    }
}
