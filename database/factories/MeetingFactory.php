<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory()->state(['role' => 'sekretaris_umum', 'is_active' => true]),
            'title' => 'Evaluasi Program Kerja',
            'category' => 'monthly',
            'scheduled_at' => now()->addDay(),
            'location' => 'Sekretariat HIMSI',
            'status' => 'scheduled',
        ];
    }
}
