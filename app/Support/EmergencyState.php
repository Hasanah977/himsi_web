<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\File;
use RuntimeException;

class EmergencyState
{
    private function directory(): string
    {
        $directory = config('security.directory');
        File::ensureDirectoryExists($directory, 0700);

        return $directory;
    }

    /** @return array{maintenance: bool, frozen: bool, generation: int} */
    public function read(): array
    {
        $path = $this->directory().'/state.json';

        return is_file($path)
            ? json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR)
            : ['maintenance' => false, 'frozen' => false, 'generation' => 0];
    }

    /** @param array{maintenance?: bool, frozen?: bool} $changes */
    public function change(array $changes, bool $revokeSessions = false): array
    {
        return $this->lock('state', LOCK_EX, function () use ($changes, $revokeSessions): array {
            $state = array_replace($this->read(), $changes);
            if ($revokeSessions) {
                $state['generation']++;
            }
            $temporary = $this->directory().'/state.tmp';
            if (file_put_contents($temporary, json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX) === false || ! rename($temporary, $this->directory().'/state.json')) {
                throw new RuntimeException('Status darurat tidak dapat disimpan.');
            }

            return $state;
        });
    }

    public function traffic(Closure $callback, bool $exclusive = false): mixed
    {
        return $this->lock('traffic', $exclusive ? LOCK_EX : LOCK_SH, $callback);
    }

    private function lock(string $name, int $mode, Closure $callback): mixed
    {
        $handle = fopen($this->directory().'/'.$name.'.lock', 'c');
        if (! $handle || ! flock($handle, $mode)) {
            throw new RuntimeException('Kunci operasi darurat tidak tersedia.');
        }
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
