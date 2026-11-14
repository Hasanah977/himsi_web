<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Database\Factories\OpsTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpsTask extends Model
{
    /** @use HasFactory<OpsTaskFactory> */
    use HasFactory;

    use ProtectsPastPeriods;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['deadline' => 'date', 'version' => 'integer', 'progress' => 'integer', 'completed_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(OpsProgram::class, 'program_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
