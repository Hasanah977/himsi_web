<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengawasanAudit extends Model
{
    use ProtectsPastPeriods;

    protected $table = 'pengawasan_audits';

    protected $fillable = ['laporan_id', 'auditor_id', 'hasil', 'catatan'];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(PengawasanLaporan::class, 'laporan_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
