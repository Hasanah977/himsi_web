<?php

namespace Database\Factories;

use App\Models\PprkSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PprkSubmission> */
class PprkSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return ['department' => 'kaderisasi', 'period' => config('department.period'), 'status' => 'draft', 'version' => 0, 'revision' => 0];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'pending_archive', 'revision' => 1, 'version' => 5, 'file_path' => 'pprk/approved.pdf', 'file_name' => 'RPK.pdf']);
    }
}
