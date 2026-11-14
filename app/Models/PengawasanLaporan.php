<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengawasanLaporan extends Model
{
    use ProtectsPastPeriods;

    protected $table = 'pengawasan_laporans';

    protected $fillable = ['ops_program_id', 'dibuat_oleh', 'judul', 'nama_kegiatan', 'departemen', 'isi', 'sumber', 'status', 'lampiran_path', 'lampiran_nama'];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PengawasanAudit::class, 'laporan_id');
    }

    public function rekomendasis(): HasMany
    {
        return $this->hasMany(PengawasanRekomendasi::class, 'laporan_id');
    }

    public function eskalasis(): HasMany
    {
        return $this->hasMany(PengawasanEskalasi::class, 'laporan_id');
    }
}
