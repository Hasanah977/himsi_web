<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;

    use ProtectsPastPeriods;

    public const CATEGORIES = ['monthly' => 'Rapat Bulanan', 'quarterly' => 'Rapat Triwulan', 'board' => 'Rapat Pengurus Inti', 'presidium' => 'Rapat Presidium'];

    public const STATUSES = ['scheduled' => 'Terjadwal', 'open' => 'Absensi Dibuka', 'closed' => 'Absensi Ditutup', 'completed' => 'Selesai'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'attendance_opened_at' => 'datetime', 'attendance_closed_at' => 'datetime'];
    }

    /** @return list<string> */
    public static function participantRoles(string $category): array
    {
        return match ($category) {
            'monthly', 'quarterly' => User::OFFICER_ROLES,
            'board' => ['ketua_umum', 'bendahara_umum', 'sekretaris_umum', 'koordinator_departemen'],
            'presidium' => ['ketua_umum', 'bendahara_umum', 'sekretaris_umum'],
            default => [],
        };
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }
}
