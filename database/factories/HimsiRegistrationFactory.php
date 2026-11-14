<?php

namespace Database\Factories;

use App\Models\HimsiRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HimsiRegistration> */
class HimsiRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'pending', 'is_active' => false]),
            'nim' => fake()->unique()->numerify('D071#####'), 'angkatan' => 2024,
            'program_studi' => 'Sistem Informasi', 'phone' => '08123456789', 'status' => 'pending',
            'student_card_path' => '', 'certificate_path' => '', 'active_letter_path' => '',
        ];
    }
}
