<?php

namespace Database\Factories;

use App\Models\KbhPendaftaranSeleksi;
use App\Models\KbhPeriode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KbhPendaftaranSeleksi> */
class KbhPendaftaranSeleksiFactory extends Factory
{
    public function definition(): array
    {
        return ['periode_id' => KbhPeriode::factory(), 'user_id' => User::factory()->state(['role' => 'kbh', 'is_active' => true]),
            'position' => 'sekretaris_umum', 'status' => 'menunggu', 'khs_path' => null,
            'reviewer_id' => fn (array $attributes) => KbhPeriode::findOrFail($attributes['periode_id'])->created_by];
    }
}
