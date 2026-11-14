<?php

namespace Database\Factories;

use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OpsSubmission> */
class OpsSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return ['program_id' => OpsProgram::factory(), 'type' => 'letter', 'status' => 'draft', 'payload' => ['recipient' => 'Sekertaris', 'subject' => 'Permohonan kegiatan', 'body' => 'Mohon memproses surat kegiatan.']];
    }
}
