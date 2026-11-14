<?php

namespace Database\Seeders;

use App\Models\Pengajuan;
use App\Models\PersetujuanTandaTanganSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuratSeeder extends Seeder
{
    public function run(): void
    {
        $sekretaris = User::where('email', 'sekretaris@himsi.test')->first();
        $ketua = User::where('email', 'ketua@himsi.test')->first();
        $kadepAkademik = User::where('email', 'kadep.akademik@himsi.test')->first();
        $kadepKaderisasi = User::where('email', 'kadep.kaderisasi@himsi.test')->first();

        // 1. Surat Masuk
        $suratMasuks = [
            [
                'nomor_urut' => 1,
                'nomor_surat' => '042/BEM-FT/UND/X/2026',
                'pengirim' => 'Badan Eksekutif Mahasiswa Fakultas Teknik',
                'alamat_pengirim' => 'Gedung Kemahasiswaan Fakultas Teknik Unsulbar',
                'perihal' => 'Undangan Pembukaan Musyawarah Mahasiswa FT 2026',
                'tanggal_surat' => '2026-10-01',
                'tanggal_diterima' => '2026-10-02',
                'lampiran' => '1 Lembar Rundown',
                'disposisi' => 'Dihadiri Ketua Umum dan Sekretaris Umum HIMSI.',
                'bidang_arsip' => 'Kemahasiswaan',
                'file_surat' => 'surat_masuk/undangan-bem-ft-2026.pdf',
                'keterangan' => 'Wajib dihadiri perwakilan delegasi lembaga.',
                'dicatat_oleh' => $sekretaris?->id,
            ],
            [
                'nomor_urut' => 2,
                'nomor_surat' => '115/UN55.1/KM/2026',
                'pengirim' => 'Wakil Dekan Bidang Kemahasiswaan FT Unsulbar',
                'alamat_pengirim' => 'Dekanat FT Unsulbar',
                'perihal' => 'Pemberitahuan Rekonsiliasi Program Kerja Ormawa 2026',
                'tanggal_surat' => '2026-10-10',
                'tanggal_diterima' => '2026-10-11',
                'lampiran' => '2 Lembar Format LPJ & PPRK',
                'disposisi' => 'Tindaklanjuti bersama Sekretaris dan seluruh Koordinator Departemen.',
                'bidang_arsip' => 'Dekanat',
                'file_surat' => 'surat_masuk/edaran-dekanat-ormawa-2026.pdf',
                'keterangan' => 'Batas pengumpulan laporan akhir bulan.',
                'dicatat_oleh' => $sekretaris?->id,
            ],
        ];

        foreach ($suratMasuks as $data) {
            SuratMasuk::updateOrCreate(['nomor_surat' => $data['nomor_surat']], $data);
        }

        // 2. Surat Keluar
        $suratKeluars = [
            [
                'nomor_urut' => 1,
                'nomor_surat' => '001/HIMSI-FT/A/X/2026',
                'jenis_surat' => 'keluar',
                'kategori_penomoran' => 'A',
                'kode_lingkup' => 'INTERNAL',
                'kode_penerbit' => 'HIMSI',
                'tujuan' => 'Dekan Fakultas Teknik Universitas Sulawesi Barat',
                'perihal' => 'Permohonan Peminjaman Ruang Lab Komputer untuk Sisfo Sharing',
                'tanggal_surat' => '2026-10-05',
                'lampiran' => '1 Berkas Proposal Kegiatan',
                'isi_surat' => 'Sehubungan dengan pelaksanaan kegiatan Sisfo Sharing Series 1...',
                'file_surat' => 'surat_keluar/permohonan-lab-komputer.docx',
                'file_final' => 'surat_keluar/permohonan-lab-komputer-final.pdf',
                'status' => 'disetujui',
                'catatan_persetujuan' => 'Disetujui. Telah sesuai format persuratan organisasi.',
                'diajukan_pada' => '2026-10-05 09:00:00',
                'diputuskan_pada' => '2026-10-05 14:00:00',
                'dibuat_oleh' => $sekretaris?->id,
                'disetujui_oleh' => $ketua?->id,
            ],
            [
                'nomor_urut' => 2,
                'nomor_surat' => '002/HIMSI-FT/B/X/2026',
                'jenis_surat' => 'keluar',
                'kategori_penomoran' => 'B',
                'kode_lingkup' => 'EKSTERNAL',
                'kode_penerbit' => 'HIMSI',
                'tujuan' => 'Ketua Program Studi Sistem Informasi FT Unsulbar',
                'perihal' => 'Permohonan Delegasi Pemateri Pelatihan Character Building',
                'tanggal_surat' => '2026-10-12',
                'lampiran' => '1 Lembar TOR Pemateri',
                'isi_surat' => 'Dengan hormat, dalam rangka menyukseskan Character Building 2026...',
                'file_surat' => 'surat_keluar/permohonan-pemateri-cb.docx',
                'file_final' => 'surat_keluar/permohonan-pemateri-cb-final.pdf',
                'status' => 'disetujui',
                'catatan_persetujuan' => 'Disetujui untuk dikirim ke Kaprodi.',
                'diajukan_pada' => '2026-10-12 10:00:00',
                'diputuskan_pada' => '2026-10-12 15:30:00',
                'dibuat_oleh' => $sekretaris?->id,
                'disetujui_oleh' => $ketua?->id,
            ],
            [
                'nomor_urut' => 3,
                'nomor_surat' => '003/HIMSI-FT/A/X/2026',
                'jenis_surat' => 'keluar',
                'kategori_penomoran' => 'A',
                'kode_lingkup' => 'INTERNAL',
                'kode_penerbit' => 'HIMSI',
                'tujuan' => 'Seluruh Anggota Himpunan Mahasiswa Sistem Informasi',
                'perihal' => 'Undangan Rapat Pleno Evaluasi Tengah Periode',
                'tanggal_surat' => '2026-10-20',
                'lampiran' => '1 Lembar Agenda Rapat',
                'isi_surat' => 'Mengundang seluruh pengurus dan anggota untuk menghadiri Rapat Pleno...',
                'file_surat' => 'surat_keluar/undangan-pleno.docx',
                'status' => 'menunggu_persetujuan',
                'catatan_persetujuan' => null,
                'diajukan_pada' => '2026-10-20 08:30:00',
                'diputuskan_pada' => null,
                'dibuat_oleh' => $sekretaris?->id,
                'disetujui_oleh' => null,
            ],
        ];

        foreach ($suratKeluars as $data) {
            $surat = SuratKeluar::updateOrCreate(['nomor_surat' => $data['nomor_surat']], $data);

            if ($surat->status === 'disetujui' && $ketua) {
                // Tanda tangan persetujuan
                PersetujuanTandaTanganSurat::updateOrCreate(
                    ['surat_keluar_id' => $surat->id, 'user_id' => $ketua->id],
                    [
                        'status' => 'disetujui',
                        'catatan' => 'Disetujui dan ditandatangani secara digital.',
                        'diputuskan_pada' => $surat->diputuskan_pada,
                    ]
                );

                // Riwayat persetujuan
                DB::table('riwayat_persetujuan_surats')->updateOrInsert(
                    ['surat_keluar_id' => $surat->id, 'pengajuan_ke' => 1],
                    [
                        'status_keputusan' => 'disetujui',
                        'catatan' => 'Persetujuan penerbitan surat resmi HIMSI.',
                        'diputuskan_oleh' => $ketua->id,
                        'diputuskan_pada' => $surat->diputuskan_pada,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Penempatan TTD
                DB::table('tanda_tangan_surat_penempatans')->updateOrInsert(
                    ['surat_keluar_id' => $surat->id, 'user_id' => $ketua->id],
                    [
                        'gambar_path' => 'signatures/ttd_ketua_umum.png',
                        'halaman' => 1,
                        'x_mm' => 140.00,
                        'y_mm' => 220.00,
                        'lebar_mm' => 45.00,
                        'dikonfirmasi_pada' => $surat->diputuskan_pada ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 3. Pengajuan Kegiatan / Dana dari Departemen
        $pengajuans = [
            [
                'user_id' => $kadepAkademik?->id,
                'judul' => 'Pengajuan Workshop AI & Data Science 2026',
                'jenis_pengajuan' => 'Kegiatan Departemen',
                'deskripsi' => 'Pengadaan workshop intensif Machine Learning dan Data Science untuk mahasiswa Sistem Informasi.',
                'nominal' => 3500000.00,
                'lampiran' => 'pengajuan/proposal-workshop-ai.pdf',
                'status' => 'disetujui',
                'diproses_oleh' => $ketua?->id,
                'catatan_ketua' => 'Disetujui. Koordinasikan kebutuhan lab dan konsumsi dengan Bendahara Umum.',
                'diproses_pada' => now()->subDays(10),
            ],
            [
                'user_id' => $kadepKaderisasi?->id,
                'judul' => 'Pengajuan Anggaran Pelatihan Dasar Kepemimpinan (LDK)',
                'jenis_pengajuan' => 'Kaderisasi',
                'deskripsi' => 'Rangkaian pembinaan kader dan character building mahasiswa baru.',
                'nominal' => 5000000.00,
                'lampiran' => 'pengajuan/rab-ldk-2026.pdf',
                'status' => 'disetujui',
                'diproses_oleh' => $ketua?->id,
                'catatan_ketua' => 'Disetujui untuk dialokasikan bertahap dari kas dan sponsorship.',
                'diproses_pada' => now()->subDays(5),
            ],
        ];

        foreach ($pengajuans as $pengajuanData) {
            if ($pengajuanData['user_id']) {
                Pengajuan::updateOrCreate(
                    ['user_id' => $pengajuanData['user_id'], 'judul' => $pengajuanData['judul']],
                    $pengajuanData
                );
            }
        }
    }
}
