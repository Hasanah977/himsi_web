<?php

namespace Database\Factories;

use App\Models\CbActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CbActivity> */
class CbActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Character Building '.fake()->year(), 'period' => '2026',
            'location' => 'Kampus', 'description' => 'Kegiatan pengembangan karakter.',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'status' => 'planned',
        ];
    }
}
