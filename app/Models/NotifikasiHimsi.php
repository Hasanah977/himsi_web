<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifikasiHimsi extends Model
{
    protected $fillable = ['user_id', 'judul', 'pesan', 'url', 'kategori', 'dibaca_pada'];

    protected function casts(): array
    {
        return ['dibaca_pada' => 'datetime'];
    }

    public static function kirim(int $userId, string $judul, string $pesan, ?string $url = null, string $kategori = 'umum'): void
    {
        static::create(['user_id' => $userId, 'judul' => $judul, 'pesan' => $pesan, 'url' => $url, 'kategori' => $kategori]);
    }
}
