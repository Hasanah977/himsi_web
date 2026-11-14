<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use App\Services\AdministrationBridge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratKeluar extends Model
{
    use ProtectsPastPeriods;
    use SoftDeletes;

    protected $table = 'surat_keluars';

    protected $fillable = ['ops_submission_id',
        'nomor_urut',
        'jenis_pengajuan',
        'penandatangan_pendamping_id',
        'ttd_posisi_x',
        'ttd_posisi_y',
        'diarsipkan_pada',
        'nomor_surat',
        'jenis_surat',
        'kategori_penomoran',
        'kode_lingkup',
        'kode_penerbit',
        'tujuan',
        'perihal',
        'tanggal_surat',
        'lampiran',
        'isi_surat',
        'file_surat',
        'file_pratinjau',
        'ttd_halaman', 'ttd_lebar',
        'ttd_halaman_pendamping', 'ttd_posisi_x_pendamping',
        'ttd_posisi_y_pendamping', 'ttd_lebar_pendamping',
        'file_final',
        'status',
        'catatan_persetujuan',
        'diajukan_pada',
        'diputuskan_pada',
        'dibuat_oleh',
        'disetujui_oleh',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $letter) => AdministrationBridge::sync($letter));
    }

    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'diarsipkan_pada' => 'datetime',
            'tanggal_surat' => 'date',
            'diajukan_pada' => 'datetime',
            'diputuskan_pada' => 'datetime',
        ];
    }

    public function penandatanganPendamping(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penandatangan_pendamping_id');
    }

    public function persetujuanTandaTangan()
    {
        return $this->hasMany(PersetujuanTandaTanganSurat::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isMenungguPersetujuan(): bool
    {
        return $this->status === 'menunggu_persetujuan';
    }

    public function isDisetujui(): bool
    {
        return $this->status === 'disetujui';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }
}
