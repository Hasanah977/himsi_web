<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbhAspirasi extends Model
{
    protected $fillable = ['user_id', 'judul', 'isi', 'lampiran_path', 'lampiran_nama', 'lampiran_mime', 'status', 'response', 'responded_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
