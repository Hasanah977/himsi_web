<?php

namespace Database\Factories;

use App\Models\OfficerReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficerReport>
 */
class OfficerReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'sekretaris_umum', 'is_active' => true]),
            'role' => 'sekretaris_umum',
            'title' => fake()->sentence(4),
            'period' => config('department.period'),
            'file_path' => 'officer-reports/example.pdf',
            'file_name' => 'LPJ.pdf',
            'status' => 'pending_supervisor',
            'version' => 1,
            'history' => [],
        ];
    }
}
