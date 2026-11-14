<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateHimsiAdmin extends Command
{
    protected $signature = 'himsi:admin';

    protected $description = 'Membuat akun login portal HIMSI';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Nama lengkap'));
        $email = trim((string) $this->ask('Email'));

        if ($name === '') {
            $this->error('Nama wajib diisi.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Email sudah digunakan.');

            return self::FAILURE;
        }

        $password = (string) $this->secret(
            'Kata sandi, minimal 8 karakter'
        );

        $confirmation = (string) $this->secret(
            'Ulangi kata sandi'
        );

        if (strlen($password) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');

            return self::FAILURE;
        }

        if ($password !== $confirmation) {
            $this->error('Konfirmasi kata sandi tidak sama.');

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();

        $this->info('Akun berhasil dibuat. Silakan login.');

        return self::SUCCESS;
    }
}
