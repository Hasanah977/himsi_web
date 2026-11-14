<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Tata Kelola Periode (Masa Kepengurusan Aktif)
            GovernancePeriodSeeder::class,

            // 2. Data Pengguna, Akun Role, dan Pendaftaran Mahasiswa
            UserSeeder::class,

            // 3. Konten Publik (Pengaturan, Beranda, Visi Misi, Proker, Pengumuman, Galeri, Struktur)
            PublicContentSeeder::class,

            // 4. Spesimen Tanda Tangan Digital Pengurus Inti
            TandaTanganSeeder::class,

            // 5. Administrasi Persuratan (Surat Masuk, Surat Keluar, Persetujuan, Penempatan TTD)
            SuratSeeder::class,

            // 6. Komisi Pemilihan Umum Mahasiswa (KPUM / E-Voting)
            KpumSeeder::class,

            // 7. Modul Pelatihan & Kaderisasi Character Building (CB)
            CbSeeder::class,

            // 8. Operasional Departemen (Grup Koordinasi, Program Kerja, Tugas, Pengajuan Dana/RAB)
            OpsDepartmentSeeder::class,

            // 9. Keluarga Besar Himpunan (KBH / Rekrutmen, Aspirasi, Iuran Kas)
            KbhSeeder::class,

            // 10. Pengawasan & Rekomendasi Kinerja Organisasi
            PengawasanSeeder::class,

            // 11. Pembukuan & Keuangan Himpunan (Buku Kas Masuk/Keluar & Audit)
            FinanceSeeder::class,

            // 12. Agenda Rapat & Presensi Kehadiran Pengurus
            MeetingSeeder::class,

            // 13. Arsip Dokumen Digital Anggota (KTM, Sertifikat, SK)
            MemberDocumentSeeder::class,

            // 14. Laporan Pertanggungjawaban (LPJ) Pengurus Harian
            OfficerReportSeeder::class,

            // 15. Pengajuan Pekan Program Rencana Kerja (PPRK/RPK) Tiap Departemen
            PprkSubmissionSeeder::class,

            // 16. Log Peristiwa Keamanan Sistem
            SecurityEventSeeder::class,

            // 17. Notifikasi Sistem untuk Pengguna
            NotifikasiSeeder::class,
        ]);
    }
}
