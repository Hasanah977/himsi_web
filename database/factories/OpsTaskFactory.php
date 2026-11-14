<?php

namespace Database\Factories;

use App\Models\OpsProgram;
use App\Models\OpsTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OpsTask> */
class OpsTaskFactory extends Factory
{
    public function definition(): array
    {
        return ['program_id' => OpsProgram::factory(), 'assigned_to' => User::factory()->state(['role' => 'anggota_departemen', 'department' => 'kaderisasi', 'is_active' => true]), 'created_by' => fn (array $a) => OpsProgram::findOrFail($a['program_id'])->created_by, 'title' => 'Siapkan dokumentasi', 'description' => 'Dokumentasikan kegiatan.', 'deadline' => now()->addDays(3)->toDateString(), 'status' => 'assigned'];
    }
}
