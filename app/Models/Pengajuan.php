<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengajuan extends Model
{
    use ProtectsPastPeriods;

    protected $fillable = [
        'user_id',
        'judul',
        'jenis_pengajuan',
        'deskripsi',
        'nominal',
        'lampiran',
        'status',
        'diproses_oleh',
        'catatan_ketua',
        'diproses_pada',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'diproses_pada' => 'datetime',
        ];
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
