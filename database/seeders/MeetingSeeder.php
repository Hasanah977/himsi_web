<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('local')) {
            Meeting::factory()->has(MeetingAttendance::factory()->count(5), 'attendances')->create();
        }
    }
}
