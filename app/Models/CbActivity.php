<?php

namespace App\Models;

use Database\Factories\CbActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CbActivity extends Model
{
    /** @use HasFactory<CbActivityFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public static function labels(): array
    {
        return ['planned' => 'Terjadwal', 'running' => 'Berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    }
}
