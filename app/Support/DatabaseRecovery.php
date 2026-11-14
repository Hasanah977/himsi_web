<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DatabaseRecovery
{
    private const EXCLUDED = ['security_events', 'sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches'];

    private function path(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return 'security/backups/'.$id.'.enc';
    }

    public function listing(): array
    {
        $disk = Storage::disk(config('security.backup_disk'));

        return collect($disk->files('security/backups'))->filter(fn (string $path) => str_ends_with($path, '.json'))
            ->map(fn (string $path) => json_decode($disk->get($path), true))
            ->filter(fn ($item) => is_array($item) && isset($item['id'], $item['created_at']))
            ->sortByDesc('created_at')->take(30)->values()->all();
    }

    public function create(string $reason = 'manual'): array
    {
        $db = DB::connection();
        $driver = $db->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Backup mendukung MySQL dan SQLite.');
        }
        $schema = $db->getSchemaBuilder();
        $payload = $db->transaction(function () use ($db, $schema, $driver): array {
            $tables = [];
            foreach ($schema->getTables($driver === 'mysql' ? $db->getDatabaseName() : 'main') as $table) {
                $name = $table['name'];
                if (in_array($name, self::EXCLUDED, true) || str_starts_with($name, 'sqlite_')) {
                    continue;
                }
                if (! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                    throw new RuntimeException('Nama tabel tidak didukung.');
                }
                if ($driver === 'mysql') {
                    $definition = array_values((array) $db->select('SHOW CREATE TABLE `'.$name.'`')[0])[1];
                    if (! str_contains($definition, 'ENGINE=InnoDB')) {
                        throw new RuntimeException('Backup konsisten memerlukan tabel InnoDB.');
                    }
                    $indexes = [];
                } else {
                    $definition = $db->selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $name])->sql;
                    $indexes = array_map(fn ($index) => $index->sql, $db->select('SELECT sql FROM sqlite_master WHERE type = ? AND tbl_name = ? AND sql IS NOT NULL', ['index', $name]));
                }
                $tables[$name] = ['sql' => $definition, 'indexes' => $indexes, 'columns' => $schema->getColumnListing($name), 'rows' => $db->table($name)->get()->map(fn ($row) => (array) $row)->all()];
            }

            return ['format' => 1, 'driver' => $driver, 'database' => $db->getDatabaseName(), 'tables' => $tables];
        });
        $metadata = ['id' => (string) Str::uuid(), 'created_at' => now()->toIso8601String(), 'reason' => $reason, 'tables' => count($payload['tables']), 'rows' => collect($payload['tables'])->sum(fn ($table) => count($table['rows']))];
        $payload['metadata'] = $metadata;
        $plain = json_encode($payload, JSON_THROW_ON_ERROR);
        if (strlen($plain) > config('security.backup_limit_bytes')) {
            throw new RuntimeException('Ukuran database melewati batas backup panel (128 MB).');
        }
        $encrypted = Crypt::encryptString(base64_encode(gzencode($plain)));
        $disk = Storage::disk(config('security.backup_disk'));
        $path = $this->path($metadata['id']);
        if (! $disk->put($path, $encrypted) || ! hash_equals(hash('sha256', $encrypted), hash('sha256', $disk->get($path)))) {
            throw new RuntimeException('Backup gagal disimpan atau diverifikasi.');
        }
        $this->read($metadata['id']);
        $metadata['bytes'] = strlen($encrypted);
        if (! $disk->put(str_replace('.enc', '.json', $path), json_encode($metadata, JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Daftar backup gagal disimpan.');
        }

        return $metadata;
    }

    public function read(string $id): array
    {
        $path = $this->path($id);
        $disk = Storage::disk(config('security.backup_disk'));
        abort_unless($disk->exists($path), 404);
        try {
            if ($disk->size($path) > config('security.backup_limit_bytes') * 2) {
                throw new RuntimeException('Backup terlalu besar.');
            }
            $compressed = base64_decode(Crypt::decryptString($disk->get($path)), true);
            $plain = gzdecode($compressed, config('security.backup_limit_bytes'));
            $payload = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
            if (($payload['format'] ?? null) !== 1 || ($payload['metadata']['id'] ?? null) !== $id || empty($payload['tables']['users'])) {
                throw new RuntimeException('Format backup tidak valid.');
            }

            return $payload;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['backup' => 'Backup rusak, tidak lengkap, atau kunci enkripsinya berbeda. Tidak ada data yang dipulihkan.']);
        }
    }

    public function restore(string $id): array
    {
        $payload = $this->read($id);
        $db = DB::connection();
        $schema = $db->getSchemaBuilder();
        $driver = $db->getDriverName();
        if ($payload['driver'] !== $driver || $payload['database'] !== $db->getDatabaseName()) {
            throw ValidationException::withMessages(['backup' => 'Backup bukan berasal dari database ini.']);
        }
        if (($payload['tables']['migrations']['rows'] ?? []) !== $db->table('migrations')->get()->map(fn ($row) => (array) $row)->all()) {
            throw ValidationException::withMessages(['backup' => 'Versi struktur database berbeda. Pilih backup yang sesuai dengan versi aplikasi saat ini.']);
        }
        $currentTables = collect($schema->getTables($driver === 'mysql' ? $db->getDatabaseName() : 'main'))->pluck('name')->all();
        foreach ($currentTables as $name) {
            if (! in_array($name, self::EXCLUDED, true) && ! str_starts_with($name, 'sqlite_') && ! isset($payload['tables'][$name])) {
                throw ValidationException::withMessages(['backup' => 'Backup tidak mencakup semua tabel yang saat ini digunakan.']);
            }
        }
        foreach ($payload['tables'] as $name => $table) {
            if (in_array($name, self::EXCLUDED, true) || ! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                throw ValidationException::withMessages(['backup' => 'Isi tabel backup tidak valid.']);
            }
            if (in_array($name, $currentTables, true) && $schema->getColumnListing($name) !== $table['columns']) {
                throw ValidationException::withMessages(['backup' => 'Kolom tabel '.$name.' sudah berubah. Restore dibatalkan.']);
            }
        }
        $administrators = $db->table('users')->where('role', 'super_admin')->get()->keyBy('id');
        $users = collect($payload['tables']['users']['rows'])->keyBy('id');
        foreach ($administrators as $admin) {
            if (isset($users[$admin->id]) && $users[$admin->id]['role'] !== 'super_admin') {
                throw ValidationException::withMessages(['backup' => 'Identitas akun Super Admin bertabrakan dengan backup.']);
            }
        }
        $users = $users->filter(fn ($user) => $user['role'] !== 'super_admin');
        foreach ($administrators as $admin) {
            $users[$admin->id] = (array) $admin;
        }
        $payload['tables']['users']['rows'] = $users->map(function ($user) {
            if ($user['role'] !== 'super_admin') {
                $user['remember_token'] = null;
            }

            return $user;
        })->values()->all();
        $safetyBackup = $this->create('before_restore');
        request()->attributes->set('security_recovery', true);
        app(EmergencyState::class)->change(['maintenance' => true, 'frozen' => true], true);
        if ($driver === 'mysql') {
            $schema->disableForeignKeyConstraints();
        }
        try {
            foreach ($payload['tables'] as $name => $table) {
                if (! in_array($name, $currentTables, true)) {
                    $db->unprepared($table['sql']);
                    foreach ($table['indexes'] as $index) {
                        $db->unprepared($index);
                    }
                }
            }
            $db->transaction(function () use ($db, $schema, $driver, $payload): void {
                if ($driver === 'sqlite') {
                    $db->statement('PRAGMA defer_foreign_keys = ON');
                }
                foreach ($payload['tables'] as $name => $table) {
                    $db->table($name)->delete();
                }
                foreach ($payload['tables'] as $name => $table) {
                    foreach (array_chunk($table['rows'], 50) as $rows) {
                        $db->table($name)->insert($rows);
                    }
                }
                foreach ($schema->getTables($driver === 'mysql' ? $db->getDatabaseName() : 'main') as $table) {
                    foreach ($schema->getForeignKeys($table['name']) as $foreign) {
                        $query = $db->table($table['name'].' as child')->leftJoin($foreign['foreign_table'].' as parent', function ($join) use ($foreign): void {
                            foreach ($foreign['columns'] as $i => $column) {
                                $join->on('child.'.$column, '=', 'parent.'.$foreign['foreign_columns'][$i]);
                            }
                        });
                        foreach ($foreign['columns'] as $column) {
                            $query->whereNotNull('child.'.$column);
                        }
                        if ($query->whereNull('parent.'.$foreign['foreign_columns'][0])->exists()) {
                            throw new RuntimeException('Relasi data hasil restore tidak valid. Transaksi dibatalkan.');
                        }
                    }
                }
                if ($schema->hasTable('sessions')) {
                    $db->table('sessions')->whereNotIn('user_id', User::where('role', 'super_admin')->select('id'))->orWhereNull('user_id')->delete();
                }
                $db->table('password_reset_tokens')->delete();
            });
        } finally {
            if ($driver === 'mysql') {
                $schema->enableForeignKeyConstraints();
            }
        }

        return $safetyBackup;
    }
}
