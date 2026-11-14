<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratMasuk extends Model
{
    use ProtectsPastPeriods;

    protected $table = 'surat_masuks';

    protected $fillable = [
        'nomor_urut',
        'nomor_surat',
        'pengirim',
        'alamat_pengirim',
        'perihal',
        'tanggal_surat',
        'tanggal_diterima',
        'lampiran',
        'disposisi',
        'bidang_arsip',
        'file_surat',
        'file_pratinjau',
        'keterangan',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_diterima' => 'date',
            'nomor_urut' => 'integer',
        ];
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
