<?php

namespace Database\Seeders;

use App\Models\PprkSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PprkSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $sekretaris = User::where('email', 'sekretaris@himsi.test')->first();
        $period = config('department.period', '2026/2027');

        foreach (array_keys(config('superadmin.departments', [])) as $department) {
            $kadep = User::where('role', 'koordinator_departemen')
                ->where('department', $department)
                ->first();

            PprkSubmission::updateOrCreate(
                ['department' => $department, 'period' => $period],
                [
                    'status' => 'archived',
                    'file_path' => 'pprk/rpk_'.$department.'_2026.pdf',
                    'file_name' => 'RPK-Departemen-'.$department.'.pdf',
                    'submitted_by' => $kadep?->id,
                    'version' => 1,
                    'revision' => 0,
                    'reviews' => [
                        'status' => 'approved',
                        'note' => 'Seluruh rancangan program kerja telah disinkronkan dengan kalender akademik.',
                    ],
                    'history' => [
                        [
                            'action' => 'submitted',
                            'at' => now()->subMonths(2)->toDateTimeString(),
                            'by' => $kadep?->name ?? 'Koordinator Departemen',
                        ],
                        [
                            'action' => 'archived',
                            'at' => now()->subMonths(2)->addDays(3)->toDateTimeString(),
                            'by' => $sekretaris?->name ?? 'Sekretaris Umum',
                        ],
                    ],
                    'archived_at' => now()->subMonths(2)->addDays(3),
                    'archived_by' => $sekretaris?->id,
                ]
            );
        }
    }
}
