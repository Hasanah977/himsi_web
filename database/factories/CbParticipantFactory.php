<?php

namespace Database\Factories;

use App\Models\CbParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CbParticipant> */
class CbParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(), 'nim' => fake()->unique()->numerify('D071#####'),
            'period' => '2026', 'angkatan' => 2024, 'phone' => '08123456789', 'status' => 'pending',
        ];
    }
}
