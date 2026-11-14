<?php

namespace App\Console\Commands;

use App\Models\HimsiRegistration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportKbh extends Command
{
    protected $signature = 'himsi:import-kbh {file : CSV name,nim,email,phone,password} {--angkatan=2024} {--update-existing : Perbarui akun KBH/pending yang cocok, termasuk password}';

    protected $description = 'Impor KBH aktif tanpa verifikasi CB dan tanpa mengganti role pengurus';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('File CSV tidak ditemukan atau tidak dapat dibaca.');

            return self::FAILURE;
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return self::FAILURE;
        }
        $rows = [];
        try {
            $header = fgetcsv($handle, 0, ',', '"', '');
            if ($header) {
                $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
            }
            if ($header !== ['name', 'nim', 'email', 'phone', 'password']) {
                $this->error('Header CSV harus: name,nim,email,phone,password');

                return self::FAILURE;
            }
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($header)) {
                    $this->error('Jumlah kolom tidak sesuai pada baris '.(count($rows) + 2).'.');

                    return self::FAILURE;
                }
                $row = array_combine($header, $values);
                $row['name'] = trim($row['name']);
                $row['nim'] = strtoupper(trim($row['nim']));
                $row['email'] = strtolower(trim($row['email']));
                $row['phone'] = str_replace(['-', ' '], '', $row['phone']);
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }
        try {
            Validator::make(['rows' => $rows, 'angkatan' => $this->option('angkatan')], [
                'angkatan' => ['required', 'integer', 'between:2000,'.now()->year],
                'rows' => ['required', 'array', 'min:1'],
                'rows.*.name' => ['required', 'string', 'max:120'],
                'rows.*.nim' => ['required', 'alpha_dash', 'max:30', 'distinct'],
                'rows.*.email' => ['required', 'email', 'max:255', 'distinct'],
                'rows.*.phone' => ['required', 'regex:/^\+?[0-9]{9,15}$/'],
                'rows.*.password' => ['required', 'string', 'min:8', 'max:128'],
            ])->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        DB::transaction(function () use ($rows, &$counts) {
            foreach ($rows as $row) {
                $user = User::where('email', $row['email'])->lockForUpdate()->first();
                $registration = HimsiRegistration::where('nim', $row['nim'])->lockForUpdate()->first();
                if (($registration && $registration->user_id !== $user?->id)
                    || ($user && $user->registration && $user->registration->nim !== $row['nim'])) {
                    $this->warn('Dilewati '.$row['nim'].': NIM dan email terhubung ke identitas berbeda.');
                    $counts['skipped']++;

                    continue;
                }
                if ($user && (! in_array($user->role, ['pending', 'kbh'], true) || ! $this->option('update-existing'))) {
                    $this->warn('Dilewati '.$row['nim'].': akun sudah ada (role '.$user->role.').');
                    $counts['skipped']++;

                    continue;
                }
                $exists = $user !== null;
                $user ??= new User;
                $user->forceFill([
                    'name' => $row['name'], 'email' => $row['email'], 'password' => Hash::make($row['password']),
                    'phone' => $row['phone'], 'role' => 'kbh', 'department' => null, 'is_active' => true,
                ])->save();
                $registration = $user->registration ?? new HimsiRegistration([
                    'user_id' => $user->id, 'student_card_path' => '', 'active_letter_path' => '', 'certificate_path' => '',
                ]);
                $registration->fill([
                    'nim' => $row['nim'], 'angkatan' => (int) $this->option('angkatan'),
                    'phone' => $row['phone'], 'program_studi' => 'Sistem Informasi', 'status' => 'approved',
                    'reviewed_at' => now(), 'reviewed_by' => null,
                    'review_note' => 'Aktivasi KBH melalui impor terminal; tanpa verifikasi berkas CB.',
                    'review_version' => ($registration->review_version ?? 0) + 1,
                ])->save();
                $counts[$exists ? 'updated' : 'created']++;
            }
        });
        $this->info("Selesai: {$counts['created']} dibuat, {$counts['updated']} diperbarui, {$counts['skipped']} dilewati.");

        return self::SUCCESS;
    }
}
