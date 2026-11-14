<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengawasanRekomendasi extends Model
{
    use ProtectsPastPeriods;

    protected $table = 'pengawasan_rekomendasis';

    protected $fillable = ['laporan_id', 'dibuat_oleh', 'rekomendasi', 'tenggat', 'status', 'catatan_tindak_lanjut'];

    protected function casts(): array
    {
        return ['tenggat' => 'date'];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(PengawasanLaporan::class, 'laporan_id');
    }
}
