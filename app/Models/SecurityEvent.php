<?php

namespace App\Models;

use Database\Factories\SecurityEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    /** @use HasFactory<SecurityEventFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['context' => 'array', 'affected_count' => 'integer'];
    }
}
