<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Governance
{
    public const SOURCES = [
        'surat-masuk' => ['SuratMasuk', 'Surat Masuk'], 'surat-keluar' => ['SuratKeluar', 'Surat Keluar'],
        'program' => ['OpsProgram', 'Program Kerja'], 'pengajuan' => ['OpsSubmission', 'RAB / LPJ / Persuratan'],
        'tugas' => ['OpsTask', 'Tugas Departemen'], 'pprk' => ['PprkSubmission', 'RPK'],
        'keuangan' => ['FinanceEntry', 'Buku Kas'], 'laporan' => ['OfficerReport', 'Laporan Pengurus'],
    ];

    public const MODELS = ['SuratMasuk', 'SuratKeluar', 'OpsProgram', 'OpsSubmission', 'OpsTask', 'PprkSubmission', 'FinanceEntry', 'OfficerReport', 'Pengajuan', 'PersetujuanTandaTanganSurat', 'PengawasanAudit', 'PengawasanLaporan', 'PengawasanRekomendasi', 'PengawasanEskalasi', 'TandaTangan'];

    public static function active(): ?object
    {
        return DB::table('governance_periods')->whereNull('closed_at')->where('starts_at', '<=', now())->where('ends_at', '>', now())->first();
    }

    public static function cutoff(): ?string
    {
        return DB::table('governance_periods')->whereNotNull('closed_at')->max('ends_at');
    }

    public static function assertWritable(?Model $record = null): void
    {
        if (! DB::table('governance_periods')->exists()) {
            return;
        }
        abort_unless(self::active(), 423, 'Masa kepengurusan tidak aktif. Super Admin perlu membuka periode baru.');
        $cutoff = self::cutoff();
        abort_if($record && $record->exists && $cutoff && (! $record->getRawOriginal('created_at') || $record->getRawOriginal('created_at') < $cutoff), 423, 'Data periode sebelumnya hanya dapat dilihat. Buat catatan tindak lanjut pada periode aktif.');
    }

    public static function expire(): void
    {
        DB::transaction(function (): void {
            $period = DB::table('governance_periods')->whereNull('closed_at')->where('ends_at', '<=', now())->lockForUpdate()->first();
            if (! $period) {
                return;
            }
            self::closePeriod($period);
        });
    }

    public static function end(int $periodId): void
    {
        DB::transaction(function () use ($periodId): void {
            $period = DB::table('governance_periods')->where('id', $periodId)->whereNull('closed_at')->lockForUpdate()->first();
            abort_unless($period, 422, 'Periode ini sudah berakhir atau tidak tersedia.');
            self::closePeriod($period, true);
        });
    }

    private static function closePeriod(object $period, bool $early = false): void
    {
        DB::table('member_appointments')->whereNull('ended_at')->update(['ended_at' => $early ? now() : $period->ends_at, 'updated_at' => now()]);
        $count = User::whereIn('role', User::OFFICER_ROLES)->update(['role' => 'kbh', 'department' => null, 'welcome_signature' => null]);
        DB::table('governance_periods')->where('id', $period->id)->update([
            'ends_at' => $early ? now() : $period->ends_at,
            'closed_at' => now(),
            'updated_at' => now(),
        ]);
        SecurityAudit::record('governance_closed', 'Periode '.$period->name.' berakhir. Jabatan pengurus dicabut.', context: ['period_id' => $period->id, 'ended_early' => $early], affected: $count);
    }

    public static function source(string $type): string
    {
        abort_unless(isset(self::SOURCES[$type]), 404);

        return 'App\\Models\\'.self::SOURCES[$type][0];
    }

    public static function query(string $type, User $user): Builder
    {
        $class = self::source($type);
        $query = $class::query();
        $role = $user->role;
        abort_unless($user->is_active && ($user->isSuperAdmin() || in_array($role, User::OFFICER_ROLES, true)), 403);
        if ($user->isSuperAdmin() || $role === 'ketua_umum') {
            return $query;
        }
        if (in_array($role, ['koordinator_departemen', 'anggota_departemen'], true)) {
            abort_unless(in_array($type, ['program', 'pengajuan', 'tugas', 'pprk'], true), 403);

            return in_array($type, ['pengajuan', 'tugas'], true) ? $query->whereHas('program', fn ($q) => $q->where('department', $user->department)) : $query->where('department', $user->department);
        }
        abort_unless(in_array($type, $role === 'sekretaris_umum' ? ['surat-masuk', 'surat-keluar', 'pengajuan', 'pprk', 'laporan'] : ['keuangan', 'pengajuan', 'pprk', 'laporan'], true), 403);
        if ($type === 'laporan') {
            $query->where('role', $role);
        }

        return $query;
    }
}
