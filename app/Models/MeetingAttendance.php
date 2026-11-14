<?php

namespace App\Models;

use Database\Factories\MeetingAttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    /** @use HasFactory<MeetingAttendanceFactory> */
    use HasFactory;

    public const STATUSES = ['pending' => 'Belum Mengisi', 'present' => 'Hadir', 'excused' => 'Izin', 'absent' => 'Tidak Hadir'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['attended_at' => 'datetime', 'version' => 'integer'];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
