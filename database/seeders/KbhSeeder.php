<?php

namespace Database\Seeders;

use App\Models\KbhAspirasi;
use App\Models\KbhIuranPembayaran;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\KbhPeriode;
use App\Models\User;
use Illuminate\Database\Seeder;

class KbhSeeder extends Seeder
{
    public function run(): void
    {
        $ketua = User::where('email', 'ketua@himsi.test')->first();
        $sekretaris = User::where('email', 'sekretaris@himsi.test')->first();
        $bendahara = User::where('email', 'bendahara@himsi.test')->first();
        $kbhUser = User::where('email', 'kbh@himsi.test')->first();
        $kbhUser2 = User::where('email', 'kbh2@himsi.test')->first();
        $pendingUser = User::where('email', 'pending@himsi.test')->first();

        // 1. Periode Seleksi Pengurus KBH & Periode Voting
        $periodeSeleksi = KbhPeriode::updateOrCreate(
            ['nama' => 'Open Recruitment Pengurus HIMSI Periode 2026/2027', 'batch' => 1],
            [
                'jenis' => 'seleksi',
                'mulai_pada' => now()->subDays(20),
                'selesai_pada' => now()->addDays(10),
                'aktif' => true,
                'created_by' => $ketua?->id,
                'stage' => 'registration',
            ]
        );

        KbhPeriode::updateOrCreate(
            ['nama' => 'Voting Internal Musyawarah KBH 2026', 'batch' => null],
            [
                'jenis' => 'voting',
                'mulai_pada' => now()->subDays(40),
                'selesai_pada' => now()->subDays(35),
                'aktif' => false,
                'created_by' => $ketua?->id,
                'stage' => 'announcement',
                'completed_at' => now()->subDays(35),
            ]
        );

        // 2. Pendaftaran Seleksi Calon Pengurus
        if ($pendingUser) {
            KbhPendaftaranSeleksi::updateOrCreate(
                ['periode_id' => $periodeSeleksi->id, 'user_id' => $pendingUser->id],
                [
                    'position' => 'anggota_departemen',
                    'department_choices' => ['akademik', 'kaderisasi'],
                    'department_reasons' => [
                        'akademik' => 'Ingin berkontribusi meningkatkan kemampuan coding mahasiswa SI.',
                        'kaderisasi' => 'Memiliki pengalaman dalam kepanitiaan dan manajemen acara.',
                    ],
                    'khs_path' => 'kbh_seleksi/khs_sample.pdf',
                    'sertifikat_path' => 'kbh_seleksi/sertifikat_sample.pdf',
                    'status' => 'menunggu',
                    'catatan' => 'Berkas pendaftaran sedang dalam tahap verifikasi panitia seleksi.',
                    'reviewer_id' => $sekretaris?->id,
                    'score' => 85.50,
                    'interview_scores' => [
                        'kepemimpinan' => 85,
                        'komitmen' => 90,
                        'wawasan_organisasi' => 82,
                    ],
                    'interview_notes' => 'Komunikatif dan memiliki motivasi tinggi dalam berorganisasi.',
                ]
            );
        }

        // 3. Aspirasi Mahasiswa
        if ($kbhUser) {
            KbhAspirasi::updateOrCreate(
                ['user_id' => $kbhUser->id, 'judul' => 'Pengajuan Lisensi Software & Peningkatan Bandwidth Lab SI'],
                [
                    'isi' => 'Mohon kepada pengurus himpunan agar mengadvokasikan ke dekanat terkait kelayakan koneksi internet lab komputer serta pembaruan lisensi software development kit.',
                    'status' => 'selesai',
                    'response' => 'Terima kasih atas aspirasinya. Departemen Advokasi telah menyusun surat permohonan ke pihak Fakultas Teknik dan saat ini dalam tindak lanjut teknisi kampus.',
                    'responded_by' => $ketua?->id,
                ]
            );
        }

        if ($kbhUser2) {
            KbhAspirasi::updateOrCreate(
                ['user_id' => $kbhUser2->id, 'judul' => 'Usulan Penyelenggaraan Hackathon Antar Kampus'],
                [
                    'isi' => 'Kami mengusulkan agar HIMSI mengadakan kompetisi Hackathon 24 jam tingkat regional Sulawesi Barat untuk mengasah skill pembuatan aplikasi solusi lokal.',
                    'status' => 'diproses',
                    'response' => 'Aspirasi diterima dan sedang dibahas dalam rapat rencana kerja Departemen Akademik.',
                    'responded_by' => $sekretaris?->id,
                ]
            );
        }

        // 4. Pembayaran Iuran Kas KBH (Januari - Mei 2026)
        $nominalIuran = (int) config('himsi_iuran.nominal_per_bulan', 5000);
        $iuranMembers = [$kbhUser, $kbhUser2];

        foreach ($iuranMembers as $member) {
            if (! $member) {
                continue;
            }

            for ($bulan = 1; $bulan <= 4; $bulan++) {
                KbhIuranPembayaran::updateOrCreate(
                    ['user_id' => $member->id, 'tahun' => 2026, 'bulan' => $bulan],
                    [
                        'nominal' => $nominalIuran,
                        'status' => 'diverifikasi',
                        'bukti_path' => 'kbh_iuran/bukti_bayar_sample.jpg',
                        'bukti_nama' => 'transfer_kas_'.$bulan.'.jpg',
                        'bukti_mime' => 'image/jpeg',
                        'catatan_bendahara' => 'Pembayaran kas telah masuk ke rekening himpunan.',
                        'catatan_anggota' => 'Iuran kas bulan ke-'.$bulan,
                        'diverifikasi_oleh' => $bendahara?->id,
                        'diverifikasi_pada' => now()->subMonths(5 - $bulan),
                    ]
                );
            }

            // Bulan Mei status menunggu verifikasi
            KbhIuranPembayaran::updateOrCreate(
                ['user_id' => $member->id, 'tahun' => 2026, 'bulan' => 5],
                [
                    'nominal' => $nominalIuran,
                    'status' => 'menunggu_verifikasi',
                    'bukti_path' => 'kbh_iuran/bukti_bayar_sample.jpg',
                    'bukti_nama' => 'transfer_kas_5.jpg',
                    'bukti_mime' => 'image/jpeg',
                    'catatan_bendahara' => null,
                    'catatan_anggota' => 'Iuran kas bulan Mei 2026',
                    'diverifikasi_oleh' => null,
                    'diverifikasi_pada' => null,
                ]
            );
        }
    }
}
