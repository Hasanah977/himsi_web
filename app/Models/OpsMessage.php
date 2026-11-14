<?php

namespace App\Models;

use Database\Factories\OpsMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpsMessage extends Model
{
    /** @use HasFactory<OpsMessageFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(OpsGroup::class, 'group_id');
    }
}
