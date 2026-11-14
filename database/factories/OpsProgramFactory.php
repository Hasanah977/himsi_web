<?php

namespace Database\Factories;

use App\Models\OpsProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OpsProgram> */
class OpsProgramFactory extends Factory
{
    public function definition(): array
    {
        return ['department' => 'kaderisasi', 'period' => '2026/2027', 'title' => fake()->sentence(4), 'description' => 'Rencana kegiatan departemen.', 'starts_on' => now()->toDateString(), 'ends_on' => now()->addDays(10)->toDateString(), 'location' => 'Kampus', 'target' => 'Mahasiswa Sistem Informasi', 'pic_id' => User::factory()->state(['role' => 'koordinator_departemen', 'department' => 'kaderisasi', 'is_active' => true]), 'created_by' => fn (array $a) => $a['pic_id'], 'status' => 'draft'];
    }
}
