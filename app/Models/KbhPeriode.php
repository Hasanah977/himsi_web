<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbhPeriode extends Model
{
    use HasFactory;

    protected $fillable = ['jenis', 'nama', 'mulai_pada', 'selesai_pada', 'aktif', 'batch', 'parent_id', 'created_by', 'stage', 'completed_at'];

    protected function casts(): array
    {
        return ['mulai_pada' => 'datetime', 'selesai_pada' => 'datetime', 'aktif' => 'boolean', 'batch' => 'integer', 'completed_at' => 'datetime'];
    }

    public function sedangDibuka(): bool
    {
        return $this->aktif && now()->betweenIncluded($this->mulai_pada, $this->selesai_pada);
    }

    public static function terbaru(string $jenis): ?self
    {
        return static::where('jenis', $jenis)->orderByDesc('mulai_pada')->orderByDesc('id')->first();
    }
}
