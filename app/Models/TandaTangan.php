<?php

namespace App\Models;

use App\Models\Concerns\ProtectsPastPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TandaTangan extends Model
{
    use ProtectsPastPeriods;

    protected $table = 'tanda_tangans';

    protected $fillable = [
        'user_id',
        'file_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
