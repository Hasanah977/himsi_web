<?php

namespace Database\Seeders;

use App\Models\NotifikasiHimsi;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotifikasiSeeder extends Seeder
{
    public function run(): void
    {
        $ketua = User::where('email', 'ketua@himsi.test')->first();
        $sekretaris = User::where('email', 'sekretaris@himsi.test')->first();
        $bendahara = User::where('email', 'bendahara@himsi.test')->first();
        $kadepAkademik = User::where('email', 'kadep.akademik@himsi.test')->first();
        $kbhUser = User::where('email', 'kbh@himsi.test')->first();

        $notifications = [
            [
                'user_id' => $ketua?->id,
                'judul' => 'Permintaan Persetujuan Surat Keluar',
                'pesan' => 'Sekretaris Umum telah mengajukan draf Surat Keluar Nomor 003/HIMSI-FT/A/X/2026 untuk permohonan tanda tangan.',
                'url' => route('ketua-umum.persetujuan-surat.index', [], false),
                'kategori' => 'persuratan',
                'dibaca_pada' => null,
            ],
            [
                'user_id' => $sekretaris?->id,
                'judul' => 'Surat Keluar Telah Disetujui',
                'pesan' => 'Ketua Umum telah menandatangani Surat Keluar Nomor 001/HIMSI-FT/A/X/2026.',
                'url' => route('sekretaris.surat-keluar.index', [], false),
                'kategori' => 'persuratan',
                'dibaca_pada' => now()->subDay(),
            ],
            [
                'user_id' => $bendahara?->id,
                'judul' => 'Pembayaran Iuran Kas Baru',
                'pesan' => 'Anggota KBH telah mengunggah bukti pembayaran iuran kas bulan Mei 2026.',
                'url' => route('bendahara.iuran.index', [], false),
                'kategori' => 'keuangan',
                'dibaca_pada' => null,
            ],
            [
                'user_id' => $kadepAkademik?->id,
                'judul' => 'Tugas Program Kerja Diserahkan',
                'pesan' => 'Anggota departemen telah memperbarui progres tugas Sisfo Code Lab menjadi 100%.',
                'url' => route('department.dashboard', ['department' => 'akademik'], false),
                'kategori' => 'operasional',
                'dibaca_pada' => now()->subHours(5),
            ],
            [
                'user_id' => $kbhUser?->id,
                'judul' => 'Tanggapan Aspirasi Diberikan',
                'pesan' => 'Ketua Umum telah menanggapi aspirasi Anda mengenai fasilitas internet laboratorium.',
                'url' => route('kbh.aspirasi.index', [], false),
                'kategori' => 'kbh',
                'dibaca_pada' => now()->subDays(2),
            ],
        ];

        foreach ($notifications as $data) {
            if ($data['user_id']) {
                NotifikasiHimsi::updateOrCreate(
                    [
                        'user_id' => $data['user_id'],
                        'judul' => $data['judul'],
                    ],
                    $data
                );
            }
        }
    }
}
