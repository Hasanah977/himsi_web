<?php

namespace App\Support;

use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\PengawasanEskalasi;
use App\Models\PengawasanLaporan;
use App\Models\PengawasanRekomendasi;
use App\Models\PprkSubmission;
use App\Models\SuratKeluar;
use Illuminate\Support\Collection;

class LeadershipDashboard
{
    /** @return array<string, mixed> */
    public static function chair(): array
    {
        $letters = SuratKeluar::where('status', 'menunggu_persetujuan')->whereNull('diarsipkan_pada')->get();
        $submissions = OpsSubmission::with('program')->whereIn('status', ['pending_chair', 'pending_officers'])->get()
            ->filter(fn (OpsSubmission $item): bool => ($item->status === 'pending_chair' || ($item->type === 'rpk' && ! isset($item->payload['approvals']['ketua_umum'])))
                && ! SuratKeluar::where('ops_submission_id', $item->id)->exists());
        $plans = PprkSubmission::where('status', 'pending_officers')->get()
            ->filter(fn (PprkSubmission $item): bool => ! isset($item->reviews['ketua_umum']));
        $escalations = PengawasanEskalasi::with('laporan')->where('status', 'menunggu')->latest()->get();
        $decisions = collect();
        foreach ($letters as $letter) {
            $decisions->push(['title' => $letter->perihal, 'detail' => $letter->nomor_surat ?: 'Surat keluar', 'status' => 'Menunggu persetujuan', 'url' => route('ketua-umum.persetujuan-surat.show', $letter), 'at' => $letter->created_at]);
        }
        foreach ($submissions as $item) {
            $decisions->push(['title' => config('department.types.'.$item->type).' · '.$item->program?->title, 'detail' => 'Pengajuan #'.$item->id, 'status' => 'Menunggu keputusan', 'url' => route('department.review', $item), 'at' => $item->created_at]);
        }
        foreach ($plans as $item) {
            $decisions->push(['title' => 'RPK · '.config('superadmin.departments.'.$item->department, $item->department), 'detail' => $item->period, 'status' => 'Menunggu verifikasi', 'url' => route('department.pprk'), 'at' => $item->created_at]);
        }
        foreach ($escalations as $item) {
            $decisions->push(['title' => $item->laporan?->judul ?? 'Eskalasi pengawas', 'detail' => $item->alasan, 'status' => 'Eskalasi menunggu', 'url' => route('ketua-umum.eskalasi-pengawas.index'), 'at' => $item->created_at]);
        }
        $programCounts = OpsProgram::selectRaw('department, status, COUNT(*) as total')->groupBy('department', 'status')->get();
        $departments = collect(config('superadmin.departments'))->map(function (string $label, string $key) use ($programCounts): array {
            $counts = $programCounts->where('department', $key)->pluck('total', 'status');

            return ['label' => $label, 'waiting' => collect(['draft', 'pending', 'revision', 'approved'])->sum(fn (string $status): int => (int) ($counts[$status] ?? 0)), 'running' => (int) ($counts['running'] ?? 0), 'completed' => (int) ($counts['completed'] ?? 0)];
        });
        $ready = SuratKeluar::where('status', 'disetujui')->whereNull('file_final')->whereNull('diarsipkan_pada')->whereNotNull('file_pratinjau')->count();

        return [
            'stats' => ['Menunggu Keputusan' => $decisions->count(), 'Surat Belum Final' => $ready, 'Program Berjalan' => OpsProgram::where('status', 'running')->count(), 'Eskalasi Aktif' => $escalations->count()],
            'decisions' => $decisions->sortBy('at')->take(5), 'departments' => $departments,
            'escalations' => $escalations->take(4),
            'recentApprovals' => SuratKeluar::whereIn('status', ['disetujui', 'ditolak'])->whereNotNull('diputuskan_pada')->latest('diputuskan_pada')->limit(4)->get(),
        ];
    }

    /** @return array<string, mixed> */
    public static function supervisor(): array
    {
        $counts = PengawasanLaporan::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $recommendations = PengawasanRekomendasi::with('laporan')->whereIn('status', ['terbuka', 'ditindaklanjuti']);
        $escalations = PengawasanEskalasi::with('laporan')->where('status', 'menunggu');
        $programIds = PengawasanLaporan::whereNotNull('ops_program_id')->select('ops_program_id');

        return [
            'stats' => ['Laporan Menunggu Tinjauan' => (int) ($counts['masuk'] ?? 0), 'Rekomendasi Aktif' => (clone $recommendations)->count(), 'Eskalasi Menunggu Keputusan' => (clone $escalations)->count(), 'Laporan Selesai' => (int) ($counts['selesai'] ?? 0)],
            'reportSegments' => self::segments(['Menunggu Tinjauan' => (int) ($counts['masuk'] ?? 0), 'Dalam Tindak Lanjut' => (int) ($counts['proses'] ?? 0), 'Perlu Perbaikan' => (int) ($counts['perlu_perbaikan'] ?? 0), 'Selesai' => (int) ($counts['selesai'] ?? 0)]),
            'recommendations' => $recommendations->latest()->limit(4)->get(),
            'escalations' => $escalations->latest()->limit(4)->get(),
            'reports' => PengawasanLaporan::latest()->limit(5)->get(),
            'monitored' => OpsProgram::whereIn('id', $programIds)->count(),
            'needsAttention' => OpsProgram::whereIn('id', PengawasanLaporan::where('status', 'perlu_perbaikan')->whereNotNull('ops_program_id')->select('ops_program_id'))->count(),
            'completedPrograms' => OpsProgram::whereIn('id', $programIds)->where('status', 'completed')->count(),
        ];
    }

    /** @param array<string, int> $counts
     * @return Collection<int, array{label: string, value: int, color: string}>
     */
    public static function segments(array $counts): Collection
    {
        $colors = ['#ffd21c', '#3284ff', '#ef6464', '#17376b', '#929eb3'];

        return collect($counts)->map(fn (int $value, string $label): array => ['label' => $label, 'value' => $value])->values()
            ->map(fn (array $item, int $index): array => $item + ['color' => $colors[$index % count($colors)]]);
    }
}
