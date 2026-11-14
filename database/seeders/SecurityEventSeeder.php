<?php

namespace Database\Seeders;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\EmergencyState;
use Illuminate\Database\Seeder;

class SecurityEventSeeder extends Seeder
{
    public function run(): void
    {
        app(EmergencyState::class)->read();

        $superadmin = User::where('email', 'superadmin@himsi.test')->first();
        $ketua = User::where('email', 'ketua@himsi.test')->first();

        $events = [
            [
                'actor_id' => $superadmin?->id,
                'actor_name' => $superadmin?->name ?? 'Super Admin',
                'actor_role' => 'super_admin',
                'event' => 'system_init',
                'severity' => 'info',
                'summary' => 'Inisialisasi sistem kepengurusan baru HIMSI Periode 2026/2027.',
                'ip_address' => '127.0.0.1',
                'route' => 'superadmin.dashboard',
                'affected_count' => 1,
                'context' => ['version' => '2.0.0', 'environment' => 'production'],
            ],
            [
                'actor_id' => $ketua?->id,
                'actor_name' => $ketua?->name ?? 'Ketua Umum',
                'actor_role' => 'ketua_umum',
                'event' => 'digital_signature',
                'severity' => 'info',
                'summary' => 'Persetujuan dan pembubuhan tanda tangan digital pada surat permohonan izin ruang.',
                'ip_address' => '127.0.0.1',
                'route' => 'ketua-umum.persetujuan-surat.index',
                'affected_count' => 1,
                'context' => ['surat_id' => 1],
            ],
            [
                'actor_id' => null,
                'actor_name' => 'Guest',
                'actor_role' => null,
                'event' => 'login_attempt',
                'severity' => 'info',
                'summary' => 'Otentikasi berhasil melalui portal login anggota HIMSI.',
                'ip_address' => '127.0.0.1',
                'route' => 'login',
                'affected_count' => 0,
                'context' => ['auth_method' => 'session'],
            ],
        ];

        foreach ($events as $eventData) {
            SecurityEvent::create($eventData);
        }
    }
}
