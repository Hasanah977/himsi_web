<?php

namespace App\Support;

use App\Models\Meeting;

class MeetingAttendanceCode
{
    /** @return array{code: string, expires_at: string, remaining_seconds: int} */
    public function current(Meeting $meeting): array
    {
        abort_unless($meeting->status === 'open' && $meeting->attendance_opened_at, 409, 'Absensi belum dibuka atau sudah ditutup.');
        $elapsed = max(0, now()->getTimestamp() - $meeting->attendance_opened_at->getTimestamp());
        $slot = intdiv($elapsed, 120);
        $expires = $meeting->attendance_opened_at->copy()->addSeconds(($slot + 1) * 120);

        return [
            'code' => strtoupper(substr(hash_hmac('sha256', $meeting->id.'|'.$meeting->attendance_opened_at->getTimestamp().'|'.$slot, config('app.key')), 0, 10)),
            'expires_at' => $expires->toIso8601String(),
            'remaining_seconds' => 120 - ($elapsed % 120),
        ];
    }
}
