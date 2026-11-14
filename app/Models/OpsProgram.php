<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Database\Factories\OpsProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpsProgram extends Model
{
    /** @use HasFactory<OpsProgramFactory> */
    use HasFactory;

    use ProtectsPastPeriods;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'version' => 'integer'];
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ops_program_members', 'program_id', 'user_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(OpsSubmission::class, 'program_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OpsTask::class, 'program_id');
    }

    public function getProgressAttribute(): int
    {
        if (! $this->tasks_count) {
            return 0;
        }

        return $this->done_count === $this->tasks_count ? 100 : min(99, (int) round($this->done_count / $this->tasks_count * 100));
    }
}
