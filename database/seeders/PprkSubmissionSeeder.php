<?php

namespace Database\Seeders;

use App\Models\PprkSubmission;
use Illuminate\Database\Seeder;

class PprkSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(config('superadmin.departments')) as $department) {
            PprkSubmission::firstOrCreate(['department' => $department, 'period' => config('department.period')]);
        }
    }
}
