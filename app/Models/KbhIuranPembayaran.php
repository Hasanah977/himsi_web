<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbhIuranPembayaran extends Model
{
    protected $table = 'kbh_iuran_pembayarans';

    protected $fillable = [
        'user_id', 'tahun', 'bulan', 'nominal', 'status',
        'bukti_path', 'bukti_nama', 'bukti_mime',
        'catatan_anggota', 'catatan_bendahara', 'diverifikasi_oleh', 'diverifikasi_pada',
    ];

    protected function casts(): array
    {
        return ['diverifikasi_pada' => 'datetime'];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bendahara(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
