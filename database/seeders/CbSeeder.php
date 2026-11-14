<?php

namespace Database\Seeders;

use App\Models\CbActivity;
use App\Models\CbParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CbSeeder extends Seeder
{
    public function run(): void
    {
        $panitiaCb = User::where('email', 'panitia.cb@himsi.test')->first();

        // 1. Peserta CB (Mahasiswa Baru Sistem Informasi)
        $participantsData = [
            ['name' => 'Aditya Pratama', 'nim' => 'D0224101', 'angkatan' => 2024, 'phone' => '082111223301', 'status' => 'accepted'],
            ['name' => 'Bella Safitri', 'nim' => 'D0224102', 'angkatan' => 2024, 'phone' => '082111223302', 'status' => 'accepted'],
            ['name' => 'Dimas Anggara', 'nim' => 'D0224103', 'angkatan' => 2024, 'phone' => '082111223303', 'status' => 'accepted'],
            ['name' => 'Farhan Maulana', 'nim' => 'D0224104', 'angkatan' => 2024, 'phone' => '082111223304', 'status' => 'accepted'],
            ['name' => 'Gita Gutawa', 'nim' => 'D0224105', 'angkatan' => 2024, 'phone' => '082111223305', 'status' => 'accepted'],
            ['name' => 'Hendra Setiawan', 'nim' => 'D0224106', 'angkatan' => 2024, 'phone' => '082111223306', 'status' => 'accepted'],
            ['name' => 'Indah Permata', 'nim' => 'D0224107', 'angkatan' => 2024, 'phone' => '082111223307', 'status' => 'accepted'],
            ['name' => 'Joko Susilo', 'nim' => 'D0224108', 'angkatan' => 2024, 'phone' => '082111223308', 'status' => 'pending'],
        ];

        $participants = [];
        foreach ($participantsData as $data) {
            $participants[] = CbParticipant::updateOrCreate(
                ['period' => '2026', 'nim' => $data['nim']],
                [
                    'name' => $data['name'],
                    'angkatan' => $data['angkatan'],
                    'phone' => $data['phone'],
                    'status' => $data['status'],
                    'selection_note' => 'Pendaftar telah melengkapi berkas administrasi.',
                ]
            );
        }

        // 2. Agenda Kegiatan CB
        $activitiesData = [
            [
                'title' => 'Stadium General: Fondasi Kepemimpinan Mahasiswa SI',
                'period' => '2026',
                'location' => 'Auditorium Unsulbar',
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->subDays(10)->addHours(4),
                'status' => 'completed',
                'description' => 'Membangun karakter kepemimpinan, integritas, dan pengenalan nilai-nilai KMFT Unsulbar.',
                'report' => 'Kegiatan dihadiri 95% peserta dengan interaksi aktif selama sesi tanya jawab.',
            ],
            [
                'title' => 'Workshop Tata Kelola Organisasi & Manajemen Konflik',
                'period' => '2026',
                'location' => 'Ruang Teater FT Unsulbar',
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->subDays(5)->addHours(4),
                'status' => 'completed',
                'description' => 'Pelatihan pemecahan masalah dan simulasi manajemen konflik dalam organisasi himpunan.',
                'report' => 'Simulasi kelompok berjalan efektif dengan studi kasus nyata organisasi kemahasiswaan.',
            ],
            [
                'title' => 'Simulasi Sidang & Tata Tertib Musyawarah',
                'period' => '2026',
                'location' => 'Sekretariat HIMSI',
                'starts_at' => now()->addDays(2),
                'ends_at' => now()->addDays(2)->addHours(5),
                'status' => 'planned',
                'description' => 'Praktik langsung tata cara persidangan mahasiswa dan teknik interupsi sesuai konstitusi himpunan.',
                'report' => null,
            ],
        ];

        foreach ($activitiesData as $data) {
            $activity = CbActivity::updateOrCreate(
                ['title' => $data['title'], 'period' => $data['period']],
                $data
            );

            // Presensi peserta
            foreach ($participants as $participant) {
                if ($participant->status === 'accepted') {
                    DB::table('cb_attendances')->updateOrInsert(
                        ['activity_id' => $activity->id, 'participant_id' => $participant->id],
                        [
                            'status' => $activity->status === 'completed' ? 'attended' : 'registered',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            // Lampiran materi
            DB::table('cb_attachments')->updateOrInsert(
                ['activity_id' => $activity->id, 'title' => 'Materi Paparan - '.$activity->title],
                [
                    'kind' => 'handout',
                    'path' => 'cb/materi_'.Str::slug($activity->title).'.pdf',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Log Aktivitas CB
        DB::table('cb_logs')->insertOrIgnore([
            [
                'actor_id' => $panitiaCb?->id,
                'action' => 'verify_participant',
                'subject' => 'Verifikasi Berkas Peserta',
                'detail' => 'Panitia CB telah memverifikasi 7 calon peserta lolos tahap administrasi.',
                'created_at' => now()->subDays(12),
            ],
            [
                'actor_id' => $panitiaCb?->id,
                'action' => 'publish_schedule',
                'subject' => 'Publikasi Jadwal Pelatihan',
                'detail' => 'Jadwal rangkaian Character Building 2026 resmi dirilis.',
                'created_at' => now()->subDays(11),
            ],
        ]);
    }
}
