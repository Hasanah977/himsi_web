<?php

namespace Database\Seeders;

use App\Models\OfficerReport;
use Illuminate\Database\Seeder;

class OfficerReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            OfficerReport::factory()->count(3)->create();
        }
    }
}
