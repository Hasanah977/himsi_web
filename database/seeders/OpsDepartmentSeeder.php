<?php

namespace Database\Seeders;

use App\Models\OpsGroup;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\OpsTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpsDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan seluruh ops_groups ada
        $this->call(OpsGroupsSeeder::class);

        // Tambah grup KBH bila belum ada
        DB::table('ops_groups')->insertOrIgnore([
            'system_key' => 'kbh',
            'department' => 'kbh',
            'name' => 'Keluarga Besar HIMSI',
            'description' => 'Ruang koordinasi dan diskusi seluruh anggota KBH.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $groups = OpsGroup::all()->keyBy('system_key');
        $umumGroup = $groups->get('umum');
        $kbhGroup = $groups->get('kbh');

        // 2. Hubungkan Anggota ke Ops Groups
        $allUsers = User::where('is_active', true)->get();
        foreach ($allUsers as $user) {
            // Semua anggota masuk ke ruang umum
            if ($umumGroup) {
                DB::table('ops_group_members')->updateOrInsert(
                    ['group_id' => $umumGroup->id, 'user_id' => $user->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // Anggota KBH / Mahasiswa
            if ($kbhGroup && $user->isStudentAccount()) {
                DB::table('ops_group_members')->updateOrInsert(
                    ['group_id' => $kbhGroup->id, 'user_id' => $user->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // Masukkan ke grup departemennya jika memiliki departemen
            if ($user->department && $groups->has('departemen-'.$user->department)) {
                $deptGroup = $groups->get('departemen-'.$user->department);
                DB::table('ops_group_members')->updateOrInsert(
                    ['group_id' => $deptGroup->id, 'user_id' => $user->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // 3. Program Kerja Tiap Departemen (Ops Programs)
        $programsData = [
            'akademik' => [
                'title' => 'Sisfo Code Lab & Mentoring Pemrograman',
                'description' => 'Kelas intensif belajar pemrograman web, basis data, dan algoritma bagi mahasiswa Sistem Informasi.',
                'location' => 'Laboratorium Komputer FT Unsulbar',
                'target' => 'Seluruh Mahasiswa SI Angkatan 2023 & 2024',
                'status' => 'running',
                'tasks' => [
                    ['title' => 'Penyusunan Silabus & Modul Praktikum', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Inventarisasi & Pengecekan PC Laboratorium', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Perekrutan & Briefing Mentor Sebaya', 'status' => 'working', 'progress' => 60],
                ],
                'submission' => [
                    'type' => 'budget',
                    'status' => 'approved',
                    'total' => 1500000,
                    'note' => 'RAB untuk sertifikat modul dan snack peserta workshop.',
                ],
            ],
            'humas' => [
                'title' => 'Studi Banding & Kolaborasi Industri IT',
                'description' => 'Membangun relasi eksternal dengan perusahaan teknologi dan himpunan mahasiswa universitas mitra.',
                'location' => 'Hybrid (Zoom & Kunjungan Lapangan)',
                'target' => 'Pengurus HIMSI dan Mitra Eksternal',
                'status' => 'approved',
                'tasks' => [
                    ['title' => 'Menyusun Proposal Kemitraan', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Menghubungi Mitra Industri & Kampus Sasaran', 'status' => 'working', 'progress' => 50],
                    ['title' => 'Menyiapkan Plakat Cinderamata & Rundown Acara', 'status' => 'assigned', 'progress' => 0],
                ],
                'submission' => [
                    'type' => 'letter',
                    'status' => 'approved',
                    'total' => 0,
                    'note' => 'Surat permohonan kunjungan studi banding ke mitra.',
                ],
            ],
            'advokasi' => [
                'title' => 'Advocacy Care & Forum Diskusi Mahasiswa SI',
                'description' => 'Wadah penampungan aspirasi dan pendampingan akademik serta beasiswa bagi mahasiswa sistem informasi.',
                'location' => 'Sekretariat HIMSI & Google Meet',
                'target' => 'Mahasiswa Sistem Informasi yang membutuhkan bantuan akademik',
                'status' => 'running',
                'tasks' => [
                    ['title' => 'Penyebaran Kuesioner Evaluasi Fasilitas Perkuliahan', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Audiensi dengan Pimpinan Program Studi', 'status' => 'working', 'progress' => 75],
                    ['title' => 'Publikasi Info Beasiswa Terbuka', 'status' => 'done', 'progress' => 100],
                ],
                'submission' => null,
            ],
            'kaderisasi' => [
                'title' => 'Character Building & Malam Keakraban 2026',
                'description' => 'Pembinaan karakter dasar, solidaritas, kepemimpinan, dan etika berorganisasi untuk mahasiswa baru.',
                'location' => 'Gedung LPMP & Pantai Bahari Majene',
                'target' => 'Mahasiswa Baru Sistem Informasi 2024',
                'status' => 'running',
                'tasks' => [
                    ['title' => 'Pendaftaran & Seleksi Berkas Peserta CB', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Koordinasi Pemateri Stadium General', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Survei dan Perizinan Lokasi Outbound', 'status' => 'working', 'progress' => 80],
                ],
                'submission' => [
                    'type' => 'budget',
                    'status' => 'approved',
                    'total' => 3500000,
                    'note' => 'Anggaran operasional logistik, konsumsi, dan tenda kegiatan.',
                ],
            ],
            'dana_usaha' => [
                'title' => 'HIMSI Merchandise & Creative Store',
                'description' => 'Penjualan merchandise resmi HIMSI (PDH, kaos, gantungan kunci, sticker) untuk kemandirian kas.',
                'location' => 'Online Store HIMSI & Stan Kampus',
                'target' => 'Alumni, Mahasiswa, dan Masyarakat Umum',
                'status' => 'running',
                'tasks' => [
                    ['title' => 'Finalisasi Desain Merchandise & Mockup', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Pemesanan Sampel Vendor Konveksi', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Membuka Pre-Order Batch 1', 'status' => 'working', 'progress' => 70],
                ],
                'submission' => [
                    'type' => 'budget',
                    'status' => 'approved',
                    'total' => 2000000,
                    'note' => 'Modal awal produksi merchandise pre-order batch 1.',
                ],
            ],
            'kesekretariatan' => [
                'title' => 'Digitalisasi Arsip Surat & Tata Kelola Inventaris',
                'description' => 'Pengarsipan surat masuk, surat keluar, notula rapat, dan pendataan barang inventaris sekretariat.',
                'location' => 'Sekretariat HIMSI',
                'target' => 'Pengurus Himpunan Mahasiswa Sistem Informasi',
                'status' => 'completed',
                'tasks' => [
                    ['title' => 'Labelisasi & Penataan Lemari Arsip', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Input Digital Seluruh Surat Masuk & Keluar', 'status' => 'done', 'progress' => 100],
                    ['title' => 'Pengecekan dan Servis Printer Sekretariat', 'status' => 'done', 'progress' => 100],
                ],
                'submission' => [
                    'type' => 'report',
                    'status' => 'approved',
                    'total' => 450000,
                    'note' => 'LPJ pembelian binder arsip, map, dan tinta printer.',
                ],
            ],
        ];

        foreach ($programsData as $deptKey => $pData) {
            $kadep = User::where('role', 'koordinator_departemen')->where('department', $deptKey)->first();
            $anggota = User::where('role', 'anggota_departemen')->where('department', $deptKey)->first();

            if (! $kadep) {
                continue;
            }

            $program = OpsProgram::updateOrCreate(
                ['department' => $deptKey, 'period' => '2026/2027', 'title' => $pData['title']],
                [
                    'description' => $pData['description'],
                    'starts_on' => now()->subDays(15)->toDateString(),
                    'ends_on' => now()->addDays(30)->toDateString(),
                    'location' => $pData['location'],
                    'target' => $pData['target'],
                    'pic_id' => $kadep->id,
                    'created_by' => $kadep->id,
                    'status' => $pData['status'],
                    'version' => 1,
                ]
            );

            // Program Members
            DB::table('ops_program_members')->updateOrInsert(
                ['program_id' => $program->id, 'user_id' => $kadep->id]
            );
            if ($anggota) {
                DB::table('ops_program_members')->updateOrInsert(
                    ['program_id' => $program->id, 'user_id' => $anggota->id]
                );
            }

            // Tasks
            foreach ($pData['tasks'] as $tData) {
                $assignee = $anggota ?? $kadep;
                OpsTask::updateOrCreate(
                    ['program_id' => $program->id, 'title' => $tData['title']],
                    [
                        'assigned_to' => $assignee->id,
                        'created_by' => $kadep->id,
                        'description' => 'Pelaksanaan tugas untuk menyukseskan '.$program->title,
                        'deadline' => now()->addDays(7)->toDateString(),
                        'status' => $tData['status'],
                        'progress' => $tData['progress'],
                        'completed_at' => $tData['status'] === 'done' ? now()->subDay() : null,
                        'version' => 1,
                    ]
                );
            }

            // Submissions (Proposal / RAB / LPJ)
            if (! empty($pData['submission'])) {
                $sub = $pData['submission'];
                OpsSubmission::updateOrCreate(
                    ['program_id' => $program->id, 'type' => $sub['type']],
                    [
                        'status' => $sub['status'],
                        'payload' => [
                            'title' => $sub['type'].' - '.$program->title,
                            'total' => $sub['total'],
                            'justification' => $sub['note'],
                        ],
                        'total' => $sub['total'],
                        'note' => $sub['note'],
                        'file_path' => 'ops_submissions/'.strtolower($sub['type']).'-'.$deptKey.'.pdf',
                        'file_name' => $sub['type'].'-'.$deptKey.'.pdf',
                        'version' => 1,
                    ]
                );
            }

            // Riwayat aktivitas (Ops History)
            DB::table('ops_history')->insertOrIgnore([
                'program_id' => $program->id,
                'actor_id' => $kadep->id,
                'action' => 'program_created',
                'note' => 'Program kerja '.$program->title.' diinisiasi oleh Departemen '.ucfirst(str_replace('_', ' ', $deptKey)),
                'created_at' => now()->subDays(15),
            ]);

            // Pesan diskusi internal grup departemen (Ops Messages)
            if ($groups->has('departemen-'.$deptKey)) {
                $deptGroup = $groups->get('departemen-'.$deptKey);
                DB::table('ops_messages')->insertOrIgnore([
                    [
                        'group_id' => $deptGroup->id,
                        'user_id' => $kadep->id,
                        'body' => 'Rekan-rekan departemen, tolong cek daftar tugas untuk proker '.$program->title.'.',
                        'created_at' => now()->subDays(3),
                        'updated_at' => now()->subDays(3),
                    ],
                    [
                        'group_id' => $deptGroup->id,
                        'user_id' => $anggota?->id,
                        'body' => 'Siap Koordinator, tugas sedang kami tindaklanjuti.',
                        'created_at' => now()->subDays(2),
                        'updated_at' => now()->subDays(2),
                    ],
                ]);
            }
        }
    }
}
