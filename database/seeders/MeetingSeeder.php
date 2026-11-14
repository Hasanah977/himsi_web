<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sekretaris = User::where('email', 'sekretaris@himsi.test')->first();
        if (! $sekretaris) {
            return;
        }

        $allOfficers = User::whereIn('role', [
            'ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_pengawas',
            'koordinator_departemen', 'anggota_departemen',
        ])->get();

        // 1. Rapat Pleno Awal Periode
        $rapatPleno = Meeting::updateOrCreate(
            ['title' => 'Rapat Pleno Pengurus HIMSI Awal Periode 2026/2027'],
            [
                'created_by' => $sekretaris->id,
                'category' => 'plenary',
                'scheduled_at' => now()->subMonths(2),
                'location' => 'Ruang Teater Fakultas Teknik Unsulbar',
                'status' => 'completed',
                'attendance_opened_at' => now()->subMonths(2),
                'attendance_closed_at' => now()->subMonths(2)->addHours(4),
                'minutes' => 'Rapat pleno pengesahan program kerja seluruh departemen, penetapan timeline kegiatan tahunan, dan pengenalan SOP tata kelola persuratan & keuangan.',
            ]
        );

        foreach ($allOfficers as $index => $officer) {
            MeetingAttendance::updateOrCreate(
                ['meeting_id' => $rapatPleno->id, 'user_id' => $officer->id],
                [
                    'participant_name' => $officer->name,
                    'participant_role' => $officer->role,
                    'participant_department' => $officer->department,
                    'status' => ($index === 5) ? 'excused' : 'attended',
                    'source' => 'manual',
                    'attended_at' => ($index === 5) ? null : now()->subMonths(2)->addMinutes(10),
                    'recorded_by' => $sekretaris->id,
                ]
            );
        }

        // 2. Rapat Evaluasi Bulanan
        $rapatEvaluasi = Meeting::updateOrCreate(
            ['title' => 'Rapat Evaluasi Bulanan & Progres Proker Departemen'],
            [
                'created_by' => $sekretaris->id,
                'category' => 'monthly',
                'scheduled_at' => now()->addDays(3),
                'location' => 'Sekretariat HIMSI Unsulbar',
                'status' => 'scheduled',
                'attendance_opened_at' => null,
                'attendance_closed_at' => null,
                'minutes' => null,
            ]
        );

        foreach ($allOfficers as $officer) {
            MeetingAttendance::updateOrCreate(
                ['meeting_id' => $rapatEvaluasi->id, 'user_id' => $officer->id],
                [
                    'participant_name' => $officer->name,
                    'participant_role' => $officer->role,
                    'participant_department' => $officer->department,
                    'status' => 'pending',
                    'source' => null,
                    'attended_at' => null,
                    'recorded_by' => null,
                ]
            );
        }
    }
}
