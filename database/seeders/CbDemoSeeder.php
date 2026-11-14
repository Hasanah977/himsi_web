<?php

namespace Database\Seeders;

use App\Models\CbActivity;
use App\Models\CbParticipant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CbDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Seeder contoh CB hanya boleh dijalankan pada lingkungan lokal atau pengujian.');
        }
        DB::transaction(function (): void {
            $activity = CbActivity::factory()->create(['title' => '[DEMO] Character Building']);
            foreach (CbParticipant::factory()->count(3)->create(['status' => 'accepted']) as $participant) {
                DB::table('cb_attendances')->insert(['activity_id' => $activity->id, 'participant_id' => $participant->id, 'status' => 'registered', 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }
}
