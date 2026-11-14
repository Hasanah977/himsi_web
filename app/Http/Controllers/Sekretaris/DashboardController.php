<?php

namespace App\Http\Controllers\Sekretaris;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\OpsSubmission;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->administration_role === 'Sekertaris',
            403,
            'Anda tidak memiliki akses ke halaman Sekertaris.'
        );

        $today = now()->startOfDay();
        $weekStart = $today->copy()->subDays(6);

        $jumlahSuratMasuk = SuratMasuk::count();

        // Hitung langsung dari database; arsip tidak mengubah status persetujuan.
        $jumlahSuratKeluar = SuratKeluar::query()
            ->whereNull('diarsipkan_pada')->count();

        $jumlahMenungguPersetujuan = SuratKeluar::query()
            ->whereNull('diarsipkan_pada')
            ->where('status', 'menunggu_persetujuan')->count();

        $jumlahArsipSurat = SuratKeluar::query()
            ->whereNotNull('diarsipkan_pada')->count();

        $suratMasukTerbaru = SuratMasuk::query()
            ->latest('tanggal_diterima')
            ->latest('id')
            ->take(5)
            ->get();

        $alurPersuratan = [
            'Draft' => SuratKeluar::query()->whereNull('diarsipkan_pada')->where('status', 'draft')->count(),
            'Menunggu Persetujuan' => $jumlahMenungguPersetujuan,
            'Disetujui' => SuratKeluar::query()->whereNull('diarsipkan_pada')->where('status', 'disetujui')->count(),
            'Dikirim / Diarsipkan' => SuratKeluar::query()->whereNotNull('diarsipkan_pada')->count(),
        ];

        $suratKeluarHarian = SuratKeluar::query()
            ->whereBetween('created_at', [$weekStart, $today->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (SuratKeluar $surat) => $surat->created_at->format('Y-m-d'));

        $statistikSuratKeluar = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $suratKeluarHarian): array {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'label' => $date->translatedFormat('D'),
                'tanggal' => $date->format('d M'),
                'jumlah' => $suratKeluarHarian->get($date->format('Y-m-d'), collect())->count(),
            ];
        });

        $agendaTerdekat = Meeting::query()
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', ['scheduled', 'open'])
            ->orderBy('scheduled_at')
            ->take(3)
            ->get();

        $jumlahAgendaTujuhHari = Meeting::query()
            ->whereBetween('scheduled_at', [now(), now()->copy()->addDays(7)])
            ->whereIn('status', ['scheduled', 'open'])
            ->count();

        $jumlahLpjPerluDitinjau = OpsSubmission::query()
            ->where('type', 'report')
            ->where('status', 'pending_archive')
            ->count();

        $jumlahLpjDiarsipkan = OpsSubmission::query()
            ->where('type', 'report')
            ->where('status', 'archived')
            ->count();

        return view('sekretaris.dashboard', compact(
            'jumlahSuratMasuk',
            'jumlahSuratKeluar',
            'jumlahMenungguPersetujuan',
            'jumlahArsipSurat',
            'suratMasukTerbaru',
            'alurPersuratan',
            'statistikSuratKeluar',
            'agendaTerdekat',
            'jumlahAgendaTujuhHari',
            'jumlahLpjPerluDitinjau',
            'jumlahLpjDiarsipkan'
        ));
    }
}
