<?php

namespace Database\Seeders;

use App\Models\OpsProgram;
use App\Models\PengawasanAudit;
use App\Models\PengawasanEskalasi;
use App\Models\PengawasanLaporan;
use App\Models\PengawasanRekomendasi;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengawasanSeeder extends Seeder
{
    public function run(): void
    {
        $pengawas = User::where('email', 'pengawas@himsi.test')->first();
        $ketua = User::where('email', 'ketua@himsi.test')->first();
        $prokerAkademik = OpsProgram::where('department', 'akademik')->first();
        $prokerKaderisasi = OpsProgram::where('department', 'kaderisasi')->first();

        if (! $pengawas) {
            return;
        }

        // 1. Laporan Pengawasan Program Kerja
        $laporan1 = PengawasanLaporan::updateOrCreate(
            ['judul' => 'Pengawasan Pelaksanaan Sisfo Code Lab Sesi 1'],
            [
                'dibuat_oleh' => $pengawas->id,
                'nama_kegiatan' => 'Sisfo Code Lab & Mentoring Pemrograman',
                'departemen' => 'akademik',
                'isi' => 'Pelaksanaan mentoring sesi 1 berlangsung tertib dengan persentase kehadiran 90%. Materi pengenalan dasar algoritma berjalan lancar, namun alokasi waktu sesi tanya jawab dirasa kurang memadai.',
                'sumber' => 'input_manual',
                'status' => 'selesai',
                'ops_program_id' => $prokerAkademik?->id,
            ]
        );

        // Audit Pengawas
        $audit1 = PengawasanAudit::updateOrCreate(
            ['laporan_id' => $laporan1->id],
            [
                'auditor_id' => $pengawas->id,
                'hasil' => 'Memuaskan',
                'catatan' => 'Kesesuaian dengan RPK mencapai 92%. Penggunaan anggaran dan kehadiran peserta terdokumentasi rapi.',
            ]
        );

        // Rekomendasi Pengawas
        $rekomendasi1 = PengawasanRekomendasi::updateOrCreate(
            ['laporan_id' => $laporan1->id],
            [
                'dibuat_oleh' => $pengawas->id,
                'rekomendasi' => 'Menambahkan waktu mentoring mandiri 30 menit setelah materi inti untuk asistensi koding.',
                'tenggat' => now()->addDays(14)->toDateString(),
                'status' => 'selesai',
                'catatan_tindak_lanjut' => 'Koordinator Departemen Akademik telah menyesuaikan susunan acara sesi berikutnya.',
            ]
        );

        // Laporan Pengawasan 2 (Kaderisasi)
        $laporan2 = PengawasanLaporan::updateOrCreate(
            ['judul' => 'Monitoring Kesiapan Logistik & Tempat Outbound CB 2026'],
            [
                'dibuat_oleh' => $pengawas->id,
                'nama_kegiatan' => 'Character Building & Malam Keakraban 2026',
                'departemen' => 'kaderisasi',
                'isi' => 'Panitia telah melakukan survei lokasi outbound. Namun izin keramaian dan ambulans siaga masih dalam tahap koordinasi.',
                'sumber' => 'input_manual',
                'status' => 'rekomendasi',
                'ops_program_id' => $prokerKaderisasi?->id,
            ]
        );

        $rekomendasi2 = PengawasanRekomendasi::updateOrCreate(
            ['laporan_id' => $laporan2->id],
            [
                'dibuat_oleh' => $pengawas->id,
                'rekomendasi' => 'Menyelesaikan surat izin kepolisian dan koordinasi posko kesehatan maksimal H-7 kegiatan.',
                'tenggat' => now()->addDays(7)->toDateString(),
                'status' => 'terbuka',
                'catatan_tindak_lanjut' => 'Sedang dalam pengurusan surat pengantar dekanat.',
            ]
        );

        // Eskalasi Pengawas ke Ketua Umum
        PengawasanEskalasi::updateOrCreate(
            ['laporan_id' => $laporan2->id, 'rekomendasi_id' => $rekomendasi2->id],
            [
                'diajukan_oleh' => $pengawas->id,
                'alasan' => 'Dibutuhkan tanda tangan cepat Ketua Umum untuk disposisi surat pengantar ke Dekanat.',
                'status' => 'selesai',
                'keputusan' => 'Ketua Umum telah menandatangani surat pengantar dan menginstruksikan pendampingan langsung panitia.',
                'diputuskan_oleh' => $ketua?->id,
                'diputuskan_pada' => now()->subDay(),
            ]
        );
    }
}
