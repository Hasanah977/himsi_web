<?php

namespace App\Console\Commands;

use App\Models\PublicContent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupHimsiSuperAdmin extends Command
{
    protected $signature = 'himsi:setup-superadmin';

    protected $description =
        'Menyalin konten awal dan menetapkan akun Super Admin';

    public function handle(): int
    {
        $email = trim((string) $this->ask(
            'Email akun yang ingin dijadikan Super Admin'
        ));

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error(
                'Akun tidak ditemukan. Buat dahulu dengan php artisan himsi:admin.'
            );

            return self::FAILURE;
        }

        DB::transaction(function () use ($user) {
            $user->role = 'super_admin';
            $user->department = null;
            $user->is_active = true;
            $user->save();

            PublicContent::firstOrCreate(
                [
                    'section' => 'settings',
                    'slug' => 'site',
                ],
                [
                    'payload' => config('superadmin.defaults'),
                    'position' => 0,
                    'is_published' => true,
                ]
            );

            foreach ([
                'programs',
                'announcements',
                'gallery',
                'leaders',
                'departments',
            ] as $section) {
                // Tidak menimpa section yang sudah memiliki data.
                if (PublicContent::where('section', $section)->exists()) {
                    continue;
                }

                foreach (config('himsi.'.$section, []) as $index => $item) {
                    PublicContent::create([
                        'section' => $section,
                        'slug' => $item['slug'] ?? null,
                        'payload' => $item,
                        'position' => $index + 1,
                        'is_published' => true,
                    ]);
                }
            }
        });

        $this->info('Super Admin dan konten awal berhasil disiapkan.');

        return self::SUCCESS;
    }
}
