<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Database\Factories\OpsSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpsSubmission extends Model
{
    /** @use HasFactory<OpsSubmissionFactory> */
    use HasFactory;

    use ProtectsPastPeriods;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'version' => 'integer', 'total' => 'integer'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(OpsProgram::class, 'program_id');
    }
}
