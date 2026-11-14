<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GovernancePeriodSeeder extends Seeder
{
    public function run(): void
    {
        // Periode masa lalu (telah ditutup)
        DB::table('governance_periods')->updateOrInsert(
            ['name' => 'Periode 2025/2026'],
            [
                'starts_at' => '2025-01-01 00:00:00',
                'ends_at' => '2025-12-31 23:59:59',
                'closed_at' => '2025-12-31 23:59:59',
                'created_at' => '2025-01-01 08:00:00',
                'updated_at' => '2025-12-31 23:59:59',
            ]
        );

        // Periode aktif saat ini
        DB::table('governance_periods')->updateOrInsert(
            ['name' => 'Periode 2026/2027'],
            [
                'starts_at' => '2026-01-01 00:00:00',
                'ends_at' => '2027-01-01 00:00:00',
                'closed_at' => null,
                'created_at' => '2026-01-01 08:00:00',
                'updated_at' => now(),
            ]
        );
    }
}
