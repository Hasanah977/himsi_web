<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbhPendaftaranSeleksi extends Model
{
    use HasFactory;

    protected $fillable = ['periode_id', 'user_id', 'khs_path', 'sertifikat_path', 'status', 'catatan', 'interview_at', 'interview_location', 'score', 'documents', 'position', 'department_choices', 'department_reasons', 'reviewer_id', 'interview_scores', 'interview_notes', 'assigned_department', 'appointed_role', 'reviewed_at'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'interview_at' => 'datetime', 'documents' => 'array', 'department_choices' => 'array', 'department_reasons' => 'array', 'interview_scores' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(KbhPeriode::class, 'periode_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
