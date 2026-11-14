<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Database\Factories\PprkSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PprkSubmission extends Model
{
    /** @use HasFactory<PprkSubmissionFactory> */
    use HasFactory;

    use ProtectsPastPeriods;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reviews' => 'array', 'history' => 'array', 'version' => 'integer', 'revision' => 'integer', 'archived_at' => 'datetime'];
    }

    public static function approvedFor(string $department, string $period): bool
    {
        return self::where('department', $department)->where('period', $period)->whereIn('status', ['pending_archive', 'archived'])->exists();
    }
}
