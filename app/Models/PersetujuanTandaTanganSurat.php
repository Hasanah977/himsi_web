<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;

class PersetujuanTandaTanganSurat extends Model
{
    use ProtectsPastPeriods;

    protected $fillable = ['surat_keluar_id', 'user_id', 'status', 'catatan', 'diputuskan_pada'];

    protected function casts(): array
    {
        return ['diputuskan_pada' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function suratKeluar()
    {
        return $this->belongsTo(SuratKeluar::class);
    }
}
