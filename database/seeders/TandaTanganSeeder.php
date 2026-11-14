<?php

namespace Database\Seeders;

use App\Models\TandaTangan;
use App\Models\User;
use Illuminate\Database\Seeder;

class TandaTanganSeeder extends Seeder
{
    public function run(): void
    {
        $officers = [
            'ketua@himsi.test' => 'signatures/ttd_ketua_umum.png',
            'sekretaris@himsi.test' => 'signatures/ttd_sekretaris_umum.png',
            'bendahara@himsi.test' => 'signatures/ttd_bendahara_umum.png',
            'pengawas@himsi.test' => 'signatures/ttd_pengawas.png',
        ];

        foreach ($officers as $email => $path) {
            $user = User::where('email', $email)->first();
            if ($user) {
                TandaTangan::updateOrCreate(
                    ['user_id' => $user->id],
                    ['file_path' => $path]
                );
            }
        }
    }
}
