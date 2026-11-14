<?php

namespace Database\Seeders;

use App\Support\EmergencyState;
use Illuminate\Database\Seeder;

class SecurityEventSeeder extends Seeder
{
    public function run(): void
    {
        app(EmergencyState::class)->read();
    }
}
