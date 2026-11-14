<?php

namespace Database\Factories;

use App\Models\SecurityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SecurityEvent> */
class SecurityEventFactory extends Factory
{
    public function definition(): array
    {
        return ['event' => 'activity', 'severity' => 'info', 'summary' => 'Permintaan perubahan data diproses.', 'ip_address' => '127.0.0.1', 'context' => []];
    }

    public function danger(): static
    {
        return $this->state(fn () => ['event' => 'access_denied', 'severity' => 'danger', 'summary' => 'Percobaan akses tanpa izin.']);
    }
}
