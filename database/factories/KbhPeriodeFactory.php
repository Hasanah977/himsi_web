<?php

namespace Database\Factories;

use App\Models\KbhPeriode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KbhPeriode> */
class KbhPeriodeFactory extends Factory
{
    public function definition(): array
    {
        return ['jenis' => 'seleksi', 'nama' => 'Pengurus HIMSI 2026/2027', 'batch' => 1,
            'created_by' => User::factory()->state(['role' => 'ketua_umum', 'is_active' => true]),
            'mulai_pada' => now()->subDay(), 'selesai_pada' => now()->addDay(), 'aktif' => true, 'stage' => 'registration'];
    }
}
