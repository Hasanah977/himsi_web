<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAttendance>
 */
class MeetingAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'user_id' => User::factory()->state(['role' => 'anggota_departemen', 'department' => 'kaderisasi', 'is_active' => true]),
            'participant_name' => fake()->name(),
            'participant_role' => 'anggota_departemen',
            'participant_department' => 'kaderisasi',
            'status' => 'pending',
        ];
    }
}
