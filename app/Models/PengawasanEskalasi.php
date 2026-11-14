<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengawasanEskalasi extends Model
{
    use ProtectsPastPeriods;

    protected $table = 'pengawasan_eskalasis';

    protected $fillable = ['laporan_id', 'rekomendasi_id', 'diajukan_oleh', 'alasan', 'status', 'keputusan', 'diputuskan_oleh', 'diputuskan_pada'];

    protected function casts(): array
    {
        return ['diputuskan_pada' => 'datetime'];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(PengawasanLaporan::class, 'laporan_id');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function rekomendasi(): BelongsTo
    {
        return $this->belongsTo(PengawasanRekomendasi::class, 'rekomendasi_id');
    }
}
