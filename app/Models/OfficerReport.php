<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Database\Factories\OfficerReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerReport extends Model
{
    /** @use HasFactory<OfficerReportFactory> */
    use HasFactory;

    use ProtectsPastPeriods;

    public const SubmitterRoles = ['ketua_umum', 'sekretaris_umum', 'bendahara_umum'];

    public const Statuses = ['pending_supervisor' => 'Menunggu Verifikasi Pengawas', 'approved' => 'Terverifikasi', 'revision' => 'Perlu Revisi', 'rejected' => 'Ditolak'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['history' => 'array', 'version' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canRead(User $user): bool
    {
        return $user->isSuperAdmin() || $user->role === 'koordinator_pengawas' || ($this->user_id === $user->id && in_array($user->role, self::SubmitterRoles, true));
    }
}
