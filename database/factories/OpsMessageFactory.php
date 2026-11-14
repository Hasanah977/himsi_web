<?php

namespace Database\Factories;

use App\Models\OpsGroup;
use App\Models\OpsMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OpsMessage> */
class OpsMessageFactory extends Factory
{
    public function definition(): array
    {
        return ['group_id' => OpsGroup::factory(), 'user_id' => User::factory(), 'body' => 'Pesan koordinasi kegiatan.'];
    }
}
