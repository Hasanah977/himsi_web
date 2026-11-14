<?php

namespace Database\Factories;

use App\Models\OpsGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OpsGroup> */
class OpsGroupFactory extends Factory
{
    public function definition(): array
    {
        return ['department' => 'kaderisasi', 'name' => fake()->words(3, true), 'description' => 'Grup koordinasi kegiatan.'];
    }
}
