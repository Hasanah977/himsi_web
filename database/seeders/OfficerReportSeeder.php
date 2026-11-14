<?php

namespace Database\Seeders;

use App\Models\OfficerReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfficerReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ketua = User::where('email', 'ketua@himsi.test')->first();
        $sekretaris = User::where('email', 'sekretaris@himsi.test')->first();
        $bendahara = User::where('email', 'bendahara@himsi.test')->first();
        $pengawas = User::where('email', 'pengawas@himsi.test')->first();

        $reports = [
            [
                'user' => $ketua,
                'role' => 'ketua_umum',
                'title' => 'Laporan Akuntabilitas Kinerja Ketua Umum Triwulan I',
                'period' => '2026/2027',
                'file_path' => 'officer_reports/lpj_ketua_triwulan_1.pdf',
                'file_name' => 'lpj_ketua_triwulan_1.pdf',
                'status' => 'approved',
                'note' => 'Seluruh arah kebijakan umum organisasi berjalan sesuai roadmap kepengurusan.',
                'reviewed_by' => $pengawas?->id,
                'reviewed_at' => now()->subDays(15),
            ],
            [
                'user' => $sekretaris,
                'role' => 'sekretaris_umum',
                'title' => 'Laporan Rekapitulasi Administrasi & Persuratan Triwulan I',
                'period' => '2026/2027',
                'file_path' => 'officer_reports/lpj_sekretaris_triwulan_1.pdf',
                'file_name' => 'lpj_sekretaris_triwulan_1.pdf',
                'status' => 'approved',
                'note' => 'Pencatatan 25 surat masuk dan 18 surat keluar telah terdokumentasi dan diarsipkan.',
                'reviewed_by' => $pengawas?->id,
                'reviewed_at' => now()->subDays(14),
            ],
            [
                'user' => $bendahara,
                'role' => 'bendahara_umum',
                'title' => 'Laporan Realisasi Anggaran & Buku Kas Himpunan Triwulan I',
                'period' => '2026/2027',
                'file_path' => 'officer_reports/lpj_bendahara_triwulan_1.pdf',
                'file_name' => 'lpj_bendahara_triwulan_1.pdf',
                'status' => 'approved',
                'note' => 'Selisih kas fisik dan catatan digital nihil. Laporan keuangan telah diaudit.',
                'reviewed_by' => $pengawas?->id,
                'reviewed_at' => now()->subDays(13),
            ],
        ];

        foreach ($reports as $data) {
            if (! $data['user']) {
                continue;
            }

            OfficerReport::updateOrCreate(
                ['user_id' => $data['user']->id, 'title' => $data['title']],
                [
                    'role' => $data['role'],
                    'period' => $data['period'],
                    'file_path' => $data['file_path'],
                    'file_name' => $data['file_name'],
                    'status' => $data['status'],
                    'note' => $data['note'],
                    'reviewed_by' => $data['reviewed_by'],
                    'reviewed_at' => $data['reviewed_at'],
                    'version' => 1,
                    'history' => [
                        [
                            'action' => 'submitted',
                            'at' => now()->subDays(20)->toDateTimeString(),
                            'by' => $data['user']->name,
                        ],
                        [
                            'action' => 'reviewed',
                            'at' => now()->subDays(15)->toDateTimeString(),
                            'by' => $pengawas?->name ?? 'Koordinator Pengawas',
                        ],
                    ],
                ]
            );
        }
    }
}
